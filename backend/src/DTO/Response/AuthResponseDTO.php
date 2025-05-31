<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Authentication response DTO with user data and JWT token
 */
class AuthResponseDTO extends BaseResponseDTO
{
    public function __construct(
        string $message,
        public readonly string $token,
        public readonly UserResponseDTO $user,
        bool $success = true,
        ?string $timestamp = null
    ) {
        parent::__construct($message, $success, $timestamp);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'token' => $this->token,
            'user' => $this->user->toArray(),
        ]);
    }
}
