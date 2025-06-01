<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for user registration requests.
 *
 * Handles new user account creation with validation for all
 * required fields. Used by the registration system to collect
 * and validate user information before creating new accounts
 * in the platform.
 */
class RegisterRequestDTO
{
    /**
     * User's email address for account registration.
     *
     * Must be a valid email format and unique in the system.
     * Used as the primary login identifier and for sending
     * account verification and notification emails.
     */
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    public readonly string $email;

    /**
     * User's chosen password for account security.
     *
     * Must meet security requirements: minimum 8 characters,
     * containing at least one lowercase letter, one uppercase
     * letter, and one number. Will be hashed before storage.
     */
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

    /**
     * User's first (given) name.
     *
     * Required for account identification and personalization.
     * Used in user interface greetings and profile displays.
     * Must be 1-255 characters long.
     */
    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'First name cannot be empty',
        maxMessage: 'First name cannot be longer than {{ limit }} characters'
    )]
    public readonly string $firstName;

    /**
     * User's last (family) name.
     *
     * Required for account identification and personalization.
     * Used in user interface displays and profile information.
     * Must be 1-255 characters long.
     */
    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'Last name cannot be empty',
        maxMessage: 'Last name cannot be longer than {{ limit }} characters'
    )]
    public readonly string $lastName;

    /**
     * Optional unique username for the account.
     *
     * If provided, must be 3-100 characters and contain only
     * letters, numbers, and underscores. Can be used as an
     * alternative identifier for the user. If not provided,
     * email will be the primary identifier.
     */
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

    /**
     * Constructor to initialize user registration details.
     *
     * @param string $email User's email address
     * @param string $password User's password
     * @param string $firstName User's first name
     * @param string $lastName User's last name
     * @param string|null $username User's optional username
     */
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
