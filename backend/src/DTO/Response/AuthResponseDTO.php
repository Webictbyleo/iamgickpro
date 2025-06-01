<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Authentication response DTO containing user data and JWT token.
 * 
 * Returned by authentication endpoints (login, register, refresh)
 * to provide the client with the access token and user information
 * needed for authenticated API requests and UI personalization.
 */
class AuthResponseDTO extends BaseResponseDTO
{
    public function __construct(
        string $message,
        /**
         * JWT access token for API authentication.
         * 
         * Bearer token that must be included in the Authorization
         * header for subsequent authenticated API requests. Contains
         * encoded user claims and expiration information.
         */
        public readonly string $token,
        
        /**
         * Authenticated user's profile information.
         * 
         * Complete user data including ID, name, email, avatar,
         * and other profile details needed for UI personalization
         * and user identification throughout the application.
         */
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
