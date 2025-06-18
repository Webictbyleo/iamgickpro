<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * User data DTO for API responses
 */
class UserResponseDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $username,
        public readonly array $roles,
        public readonly ?string $avatar = null,
        public readonly string $plan = 'free',
        public readonly ?bool $emailVerified = null,
        public readonly ?bool $isActive = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $lastLoginAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?array $settings = null,
        public readonly ?array $stats = null,
        public readonly ?array $socialLinks = null
    ) {}

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'email' => $this->email,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'username' => $this->username,
            'roles' => $this->roles,
            'avatar' => $this->avatar,
            'plan' => $this->plan,
        ];

        // Add extended info if provided
        if ($this->emailVerified !== null) {
            $data['emailVerified'] = $this->emailVerified;
        }
        if ($this->isActive !== null) {
            $data['isActive'] = $this->isActive;
        }
        if ($this->createdAt !== null) {
            $data['createdAt'] = $this->createdAt;
        }
        if ($this->lastLoginAt !== null) {
            $data['lastLoginAt'] = $this->lastLoginAt;
        }
        if ($this->updatedAt !== null) {
            $data['updatedAt'] = $this->updatedAt;
        }
        if ($this->settings !== null) {
            $data['settings'] = $this->settings;
        }
        if ($this->stats !== null) {
            $data['stats'] = $this->stats;
        }
        if ($this->socialLinks !== null) {
            $data['socialLinks'] = $this->socialLinks;
        }

        return $data;
    }
}
