<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Message for background processing of user account deletion
 */
readonly class DeleteUserAccountMessage
{
    public function __construct(
        private int $userId,
        private string $userEmail,
        private string $requestId,
        private bool $hardDelete = false
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function isHardDelete(): bool
    {
        return $this->hardDelete;
    }
}
