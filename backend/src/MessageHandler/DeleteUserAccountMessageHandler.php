<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\DeleteUserAccountMessage;
use App\Service\UserService;
use App\Service\EmailService;
use App\Service\AuditLogService;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles background processing of user account deletion
 */
#[AsMessageHandler]
readonly class DeleteUserAccountMessageHandler
{
    public function __construct(
        private UserService $userService,
        private UserRepository $userRepository,
        private EmailService $emailService,
        private AuditLogService $auditLogService,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(DeleteUserAccountMessage $message): void
    {
        $userId = $message->getUserId();
        $requestId = $message->getRequestId();
        $hardDelete = $message->isHardDelete();
        
        try {
            $this->logger->info('Starting user account deletion', [
                'user_id' => $userId,
                'request_id' => $requestId,
                'hard_delete' => $hardDelete
            ]);
            
            $user = $this->userRepository->find($userId);
            if (!$user) {
                $this->logger->error('User not found for account deletion', [
                    'user_id' => $userId,
                    'request_id' => $requestId
                ]);
                return;
            }
            
            // Store user email for confirmation before deletion
            $userEmail = $user->getEmail();
            $userFirstName = $user->getFirstName() ?? 'User';
            
            // Perform account deletion
            $this->userService->performAccountDeletion($user, $hardDelete);
            
            // Send confirmation email
            $this->emailService->sendAccountDeletionCompletion(
                $userEmail,
                $userFirstName,
                $requestId
            );
            
            // Final audit log (user is now deleted)
            $this->logger->info('User account deletion completed', [
                'user_id' => $userId,
                'user_email' => $userEmail,
                'request_id' => $requestId,
                'hard_delete' => $hardDelete
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete user account', [
                'user_id' => $userId,
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Send error notification email
            try {
                $user = $this->userRepository->find($userId);
                if ($user) {
                    $this->emailService->sendAccountDeletionError(
                        $user->getEmail(),
                        $user->getFirstName() ?? 'User',
                        $requestId
                    );
                }
            } catch (\Exception $emailError) {
                $this->logger->error('Failed to send account deletion error email', [
                    'user_id' => $userId,
                    'request_id' => $requestId,
                    'email_error' => $emailError->getMessage()
                ]);
            }
        }
    }
}
