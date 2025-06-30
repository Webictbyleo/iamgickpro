<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\TypedResponseTrait;
use App\Service\ResponseDTOFactory;
use App\Service\RateLimitService;
use App\Service\AuditLogService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public Controller
 * 
 * Handles publicly accessible endpoints that don't require authentication,
 * such as data export downloads accessed via email links.
 */
#[Route('/api/public', name: 'api_public_')]
class PublicController extends AbstractController
{
    use TypedResponseTrait;

    public function __construct(
        private readonly ResponseDTOFactory $responseDTOFactory,
        private readonly LoggerInterface $logger,
        private readonly RateLimitService $rateLimitService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Download prepared data export file (Public Access)
     * 
     * Serves the prepared data export file for download. Files are secured
     * with request IDs and automatically expire after 7 days.
     * This endpoint is publicly accessible via email links.
     * 
     * @param string $requestId The request ID for the data export
     * @return JsonResponse The download file or error if not found/expired
     */
    #[Route('/download/{requestId}', name: 'download_file', methods: ['GET'])]
    public function downloadDataFile(string $requestId): JsonResponse
    {
        try {
            // Extract user ID from request ID for validation and rate limiting
            $requestParts = explode('_', $requestId);
            if (count($requestParts) < 3 || $requestParts[0] !== 'download') {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Invalid download request'),
                    Response::HTTP_NOT_FOUND
                );
            }
            
            $userId = (int)$requestParts[1];
            
            // Check rate limit for downloads (by IP and user ID)
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            if ($this->rateLimitService->isRateLimited('public_download_' . $clientIp, $userId, 10, 3600)) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Download rate limit exceeded'),
                    Response::HTTP_TOO_MANY_REQUESTS
                );
            }
            
            // Look for the export file
            $exportDir = $this->getParameter('kernel.project_dir') . '/storage/data_exports';
            $files = glob($exportDir . '/' . $requestId . '_*.json');
            
            if (empty($files)) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Download file not found or expired'),
                    Response::HTTP_NOT_FOUND
                );
            }
            
            $filePath = $files[0];
            
            // Check if file exists and is readable
            if (!file_exists($filePath) || !is_readable($filePath)) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Download file not accessible'),
                    Response::HTTP_NOT_FOUND
                );
            }
            
            // Read and validate file content
            $fileContent = file_get_contents($filePath);
            $exportData = json_decode($fileContent, true);
            
            if (!$exportData || !isset($exportData['metadata'])) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Invalid export file format'),
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
            
            // Check if file has expired
            $expiresAt = new \DateTimeImmutable($exportData['metadata']['expires_at']);
            if ($expiresAt < new \DateTimeImmutable()) {
                // Clean up expired file
                unlink($filePath);
                
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Download file has expired'),
                    Response::HTTP_GONE
                );
            }
            
            // Log download access
            $this->auditLogService->logPrivacyActionByUserId($userId, 'data_download_accessed', [
                'request_id' => $requestId,
                'file_size' => filesize($filePath),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'ip_address' => $clientIp,
                'access_type' => 'public_link'
            ]);
            
            // Return file content as JSON response
            return new JsonResponse($exportData['data'], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to serve public data download', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to process download'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
