<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service for audit logging of sensitive user actions
 */
readonly class AuditLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private RequestStack $requestStack
    ) {
    }

    /**
     * Log a privacy-related action
     * 
     * @param User $user The user performing the action
     * @param string $action The action being performed
     * @param array $metadata Additional metadata about the action
     */
    public function logPrivacyAction(User $user, string $action, array $metadata = []): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $ipAddress = $request?->getClientIp() ?? 'unknown';
        $userAgent = $request?->headers->get('User-Agent') ?? 'unknown';
        
        $logData = [
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail(),
            'action' => $action,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'timestamp' => new \DateTimeImmutable(),
            'metadata' => $metadata
        ];
        
        // Log to application logs
        $this->logger->info('Privacy action performed', $logData);
        
        // In a production environment, you might also want to:
        // 1. Store in a dedicated audit log table
        // 2. Send to a SIEM system
        // 3. Create compliance reports
        
        try {
            // You could create an AuditLog entity to store these in the database
            // For now, we'll just use structured logging
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Failed to persist audit log', [
                'error' => $e->getMessage(),
                'audit_data' => $logData
            ]);
        }
    }

    /**
     * Log a data access event
     * 
     * @param User $user The user accessing the data
     * @param string $dataType The type of data accessed
     * @param array $metadata Additional metadata
     */
    public function logDataAccess(User $user, string $dataType, array $metadata = []): void
    {
        $this->logPrivacyAction($user, 'data_access', array_merge($metadata, [
            'data_type' => $dataType
        ]));
    }

    /**
     * Log a data export event
     * 
     * @param User $user The user exporting data
     * @param string $exportType The type of export
     * @param array $metadata Additional metadata
     */
    public function logDataExport(User $user, string $exportType, array $metadata = []): void
    {
        $this->logPrivacyAction($user, 'data_export', array_merge($metadata, [
            'export_type' => $exportType
        ]));
    }

    /**
     * Log a data download request
     * 
     * @param User $user The user requesting download
     * @param string $requestId The unique request ID
     * @param array $metadata Additional metadata
     */
    public function logDataDownloadRequest(User $user, string $requestId, array $metadata = []): void
    {
        $this->logPrivacyAction($user, 'data_download_request', array_merge($metadata, [
            'request_id' => $requestId
        ]));
    }

    /**
     * Log an account deletion request
     * 
     * @param User $user The user requesting account deletion
     * @param string $requestId The unique request ID
     * @param array $metadata Additional metadata
     */
    public function logAccountDeletionRequest(User $user, string $requestId, array $metadata = []): void
    {
        $this->logPrivacyAction($user, 'account_deletion_request', array_merge($metadata, [
            'request_id' => $requestId
        ]));
    }

    /**
     * Log a privacy-related action by user ID (for public endpoints)
     * 
     * @param int $userId The ID of the user
     * @param string $action The action being performed
     * @param array $metadata Additional metadata about the action
     */
    public function logPrivacyActionByUserId(int $userId, string $action, array $metadata = []): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $ipAddress = $request?->getClientIp() ?? ($metadata['ip_address'] ?? 'unknown');
        $userAgent = $request?->headers->get('User-Agent') ?? ($metadata['user_agent'] ?? 'unknown');
        
        $logData = [
            'user_id' => $userId,
            'user_email' => 'unknown', // We don't have the user object in public context
            'action' => $action,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'timestamp' => new \DateTimeImmutable(),
            'metadata' => $metadata
        ];
        
        // Log to application logs
        $this->logger->info('Privacy action performed (public)', $logData);
        
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Failed to persist audit log (public)', [
                'error' => $e->getMessage(),
                'audit_data' => $logData
            ]);
        }
    }
}
