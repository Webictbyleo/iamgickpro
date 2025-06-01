<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for password change requests.
 * 
 * Handles secure password updates for authenticated users.
 * Validates the current password before allowing the change
 * and ensures the new password meets security requirements
 * with confirmation validation.
 */
class ChangePasswordRequestDTO
{
    /**
     * User's current password for verification.
     * 
     * Required to verify the user's identity before allowing
     * a password change. Must match the currently stored
     * password hash for the authenticated user.
     */
    #[Assert\NotBlank(message: 'Current password is required')]
    public readonly string $currentPassword;

    /**
     * New password that will replace the current one.
     * 
     * Must meet security requirements: minimum 8 characters,
     * containing at least one lowercase letter, one uppercase
     * letter, and one number. Will be hashed before storage.
     */
    #[Assert\NotBlank(message: 'New password is required')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Password must be at least {{ limit }} characters long'
    )]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
        message: 'Password must contain at least one lowercase letter, one uppercase letter, and one number'
    )]
    public readonly string $newPassword;

    /**
     * Confirmation of the new password.
     * 
     * Must exactly match the newPassword field to prevent
     * accidental password typos during the change process.
     * Validated through the isPasswordConfirmed() method.
     */
    #[Assert\NotBlank(message: 'Password confirmation is required')]
    public readonly string $confirmPassword;

    public function __construct(
        string $currentPassword,
        string $newPassword,
        string $confirmPassword
    ) {
        $this->currentPassword = $currentPassword;
        $this->newPassword = $newPassword;
        $this->confirmPassword = $confirmPassword;
    }

    /**
     * Validates that the new password and confirmation match.
     * 
     * Custom validation method that ensures the user has correctly
     * confirmed their new password by typing it twice identically.
     * Used by Symfony's validation system to prevent password typos.
     * 
     * @return bool True if passwords match, false otherwise
     */
    #[Assert\IsTrue(message: 'New password and confirmation password do not match')]
    public function isPasswordConfirmed(): bool
    {
        return $this->newPassword === $this->confirmPassword;
    }
}
