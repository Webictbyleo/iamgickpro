<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * User profile response DTO
 */
class UserProfileResponseDTO extends BaseResponseDTO
{
    public function __construct(
        public readonly UserResponseDTO $user,
        string $message = 'User profile retrieved successfully',
        bool $success = true,
        ?string $timestamp = null
    ) {
        parent::__construct($message, $success, $timestamp);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'user' => $this->user->toArray(),
        ]);
    }
}
