<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for user registration requests
 */
class RegisterRequestDTO
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    public readonly string $email;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Password must be at least {{ limit }} characters long'
    )]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
        message: 'Password must contain at least one lowercase letter, one uppercase letter, and one number'
    )]
    public readonly string $password;

    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'First name cannot be empty',
        maxMessage: 'First name cannot be longer than {{ limit }} characters'
    )]
    public readonly string $firstName;

    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'Last name cannot be empty',
        maxMessage: 'Last name cannot be longer than {{ limit }} characters'
    )]
    public readonly string $lastName;

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

    public function __construct(
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        ?string $username = null
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->username = $username;
    }
}
