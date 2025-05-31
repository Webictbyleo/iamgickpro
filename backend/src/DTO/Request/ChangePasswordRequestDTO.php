<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for password change requests
 */
class ChangePasswordRequestDTO
{
    #[Assert\NotBlank(message: 'Current password is required')]
    public readonly string $currentPassword;

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

    #[Assert\IsTrue(message: 'New password and confirmation password do not match')]
    public function isPasswordConfirmed(): bool
    {
        return $this->newPassword === $this->confirmPassword;
    }
}
