<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Message for background processing of user data download preparation
 */
readonly class PrepareUserDataDownloadMessage
{
    public function __construct(
        private int $userId,
        private string $requestId,
        private string $userEmail,
        private array $dataTypes = []
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    public function getDataTypes(): array
    {
        return $this->dataTypes;
    }
}
