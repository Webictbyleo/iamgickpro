<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for profile update requests
 */
class UpdateProfileRequestDTO
{
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'First name cannot be empty',
        maxMessage: 'First name cannot be longer than {{ limit }} characters'
    )]
    public readonly ?string $firstName;

    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'Last name cannot be empty',
        maxMessage: 'Last name cannot be longer than {{ limit }} characters'
    )]
    public readonly ?string $lastName;

    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'Username must be at least {{ limit }} characters long',
        maxMessage: 'Username cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_]+$/',
        message: 'Username can only contain letters, numbers, and underscores'
    )]
    public readonly ?string $username;

    #[Assert\Url(message: 'Avatar must be a valid URL')]
    public readonly ?string $avatar;

    #[Assert\Type(type: 'array', message: 'Settings must be an array')]
    public readonly ?array $settings;

    public function __construct(
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $username = null,
        ?string $avatar = null,
        ?array $settings = null
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->username = $username;
        $this->avatar = $avatar;
        $this->settings = $settings;
    }

    public function hasAnyData(): bool
    {
        return $this->firstName !== null 
            || $this->lastName !== null 
            || $this->username !== null 
            || $this->avatar !== null 
            || $this->settings !== null;
    }
}
