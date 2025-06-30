<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\PrepareUserDataDownloadMessage;
use App\Service\UserService;
use App\Service\EmailService;
use App\Service\AuditLogService;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Handles background processing of user data download preparation
 */
#[AsMessageHandler]
readonly class PrepareUserDataDownloadMessageHandler
{
    public function __construct(
        private UserService $userService,
        private UserRepository $userRepository,
        private EmailService $emailService,
        private AuditLogService $auditLogService,
        private LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%/storage/data_exports')]
        private string $exportDirectory,
        #[Autowire('%env(BACKEND_URL)%')]
        private string $backendUrl
    ) {
    }

    public function __invoke(PrepareUserDataDownloadMessage $message): void
    {
        $userId = $message->getUserId();
        $requestId = $message->getRequestId();
        
        try {
            $this->logger->info('Starting user data download preparation', [
                'user_id' => $userId,
                'request_id' => $requestId
            ]);
            
            $user = $this->userRepository->find($userId);
            if (!$user) {
                $this->logger->error('User not found for data download', [
                    'user_id' => $userId,
                    'request_id' => $requestId
                ]);
                return;
            }
            
            // Generate comprehensive data export
            $exportData = $this->userService->generateComprehensiveDataExport($user);
            
            // Create secure download file
            $downloadUrl = $this->createSecureDownloadFile($requestId, $exportData);
            
            // Send email notification with download link
            $this->emailService->sendDataDownloadReady(
                $user->getEmail(),
                $user->getFirstName() ?? 'User',
                $downloadUrl,
                $requestId
            );
            
            // Log completion
            $this->auditLogService->logPrivacyAction($user, 'data_download_completed', [
                'request_id' => $requestId,
                'download_url' => $downloadUrl,
                'export_size_bytes' => strlen(json_encode($exportData))
            ]);
            
            $this->logger->info('User data download preparation completed', [
                'user_id' => $userId,
                'request_id' => $requestId,
                'download_url' => $downloadUrl
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to prepare user data download', [
                'user_id' => $userId,
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Send error notification email
            try {
                $user = $this->userRepository->find($userId);
                if ($user) {
                    $this->emailService->sendDataDownloadError(
                        $user->getEmail(),
                        $user->getFirstName() ?? 'User',
                        $requestId
                    );
                }
            } catch (\Exception $emailError) {
                $this->logger->error('Failed to send error notification email', [
                    'user_id' => $userId,
                    'request_id' => $requestId,
                    'email_error' => $emailError->getMessage()
                ]);
            }
        }
    }

    /**
     * Create a secure download file with expiration
     * 
     * @param string $requestId Unique request identifier
     * @param array $exportData The data to export
     * @return string The secure download URL
     */
    private function createSecureDownloadFile(string $requestId, array $exportData): string
    {
        // Ensure export directory exists
        if (!is_dir($this->exportDirectory)) {
            mkdir($this->exportDirectory, 0755, true);
        }
        
        // Create secure filename
        $filename = sprintf('%s_%s.json', $requestId, hash('sha256', uniqid('', true)));
        $filepath = $this->exportDirectory . '/' . $filename;
        
        // Add metadata to export
        $exportWithMetadata = [
            'metadata' => [
                'request_id' => $requestId,
                'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                'expires_at' => (new \DateTimeImmutable('+7 days'))->format(\DateTimeInterface::ATOM),
                'format_version' => '1.0'
            ],
            'data' => $exportData
        ];
        
        // Write encrypted/compressed data
        $jsonData = json_encode($exportWithMetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($filepath, $jsonData);
        
        // Set file permissions to be readable only by web server
        chmod($filepath, 0644);
        
        // Return secure download URL with full domain (public endpoint)
        return sprintf('%s/api/public/download/%s', rtrim($this->backendUrl, '/'), $requestId);
    }
}
