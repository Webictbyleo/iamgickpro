<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\Service\UserService;
use App\Repository\UserRepository;
use App\Service\AuditLogService;
use Psr\Log\LoggerInterface;

/**
 * Public endpoint for privacy data download (no authentication required)
 */
class PublicDataController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/storage/data_exports')]
        private readonly string $exportDirectory,
        private readonly UserRepository $userRepository,
        private readonly AuditLogService $auditLogService,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/api/public/download/{requestId}', name: 'public_data_download', methods: ['GET'])]
    public function download(Request $request, string $requestId): Response
    {
        // Find the file by requestId (filename starts with requestId)
        $files = glob($this->exportDirectory . "/{$requestId}_*.json");
        if (!$files || !file_exists($files[0])) {
            return new JsonResponse([
                'code' => 404,
                'message' => 'Data export not found or expired.'
            ], 404);
        }

        $file = $files[0];
        // Optionally: check expiration in metadata
        $json = json_decode(file_get_contents($file), true);
        if (isset($json['metadata']['expires_at'])) {
            $expiresAt = \DateTimeImmutable::createFromFormat(DATE_ATOM, $json['metadata']['expires_at']);
            if ($expiresAt && $expiresAt < new \DateTimeImmutable()) {
                return new JsonResponse([
                    'code' => 410,
                    'message' => 'Data export has expired.'
                ], 410);
            }
        }

        // Log the download event (no user context)
        $userId = 0;
        if (isset($json['metadata']['user_id'])) {
            $userId = (int)$json['metadata']['user_id'];
        }
        $this->auditLogService->logPrivacyActionByUserId($userId, 'public_data_download', [
            'request_id' => $requestId,
            'ip' => $request->getClientIp(),
        ]);

        // Return the file as download
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($file)
        );
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
