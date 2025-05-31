<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Base response DTO with common fields
 */
abstract class BaseResponseDTO
{
    public function __construct(
        public readonly string $message,
        public readonly bool $success = true,
        public readonly ?string $timestamp = null
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'timestamp' => $this->timestamp ?? (new \DateTimeImmutable())->format('c'),
        ];
    }
}
