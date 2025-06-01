<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for user authentication login requests.
 * 
 * Handles user login credentials for JWT token-based authentication.
 * Used by the authentication system to validate user credentials
 * and generate access tokens for API authorization.
 */
class LoginRequestDTO
{
    /**
     * User's email address for authentication.
     * 
     * Must be a valid email format and correspond to a registered
     * user account in the system. Used as the primary identifier
     * for user authentication.
     */
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    public readonly string $email;

    /**
     * User's password for authentication.
     * 
     * Plain text password that will be verified against the
     * hashed password stored in the database. Should be handled
     * securely throughout the authentication process.
     */
    #[Assert\NotBlank(message: 'Password is required')]
    public readonly string $password;

    public function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->password = $password;
    }
}
