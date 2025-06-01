<?php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\ValueObject\UserSettings;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for user profile update requests
 * 
 * Handles updating user profile information including personal details,
 * username, avatar, and application settings. All fields are optional
 * to support partial updates.
 */
final readonly class UpdateProfileRequestDTO
{
    public function __construct(
        /**
         * User's first name for display and identification purposes
         * Must be between 1-255 characters if provided
         */
        #[Assert\Length(
            min: 1,
            max: 255,
            minMessage: 'First name cannot be empty',
            maxMessage: 'First name cannot be longer than {{ limit }} characters'
        )]
        public ?string $firstName = null,

        /**
         * User's last name for display and identification purposes  
         * Must be between 1-255 characters if provided
         */
        #[Assert\Length(
            min: 1,
            max: 255,
            minMessage: 'Last name cannot be empty',
            maxMessage: 'Last name cannot be longer than {{ limit }} characters'
        )]
        public ?string $lastName = null,

        /**
         * Unique username for the user account
         * Must be 3-100 characters, containing only letters, numbers, and underscores
         * Used for public profile identification and @mentions
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
        public ?string $username = null,

        /**
         * URL to the user's profile avatar image
         * Must be a valid URL pointing to an image file
         * Used for profile display and team collaboration identification
         */
        #[Assert\Url(message: 'Avatar must be a valid URL')]
        #[Assert\Length(max: 500, maxMessage: 'Avatar URL cannot exceed 500 characters')]
        public ?string $avatar = null,

        /**
         * User's application settings and preferences
         * Controls theme, language, notifications, auto-save, and editor behavior
         * Structured object containing all user preference configurations
         */
        public ?UserSettings $settings = null
    ) {}

    /**
     * Check if any profile data is provided for update
     * Used to validate that at least one field is being updated
     */
    public function hasAnyData(): bool
    {
        return $this->firstName !== null 
            || $this->lastName !== null 
            || $this->username !== null 
            || $this->avatar !== null 
            || $this->settings !== null;
    }

    /**
     * Convert settings to array for legacy compatibility
     * Returns the settings as an array or null if no settings provided
     */
    public function getSettingsArray(): ?array
    {
        return $this->settings?->toArray();
    }
}
