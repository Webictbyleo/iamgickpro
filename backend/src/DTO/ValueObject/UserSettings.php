<?php

declare(strict_types=1);

namespace App\DTO\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * User application settings
 */
final readonly class UserSettings
{
    public function __construct(
        /**
         * UI theme preference (light, dark, or auto)
         * @var string $theme
         */
        #[Assert\Choice(
            choices: ['light', 'dark', 'auto'],
            message: 'Theme must be light, dark, or auto'
        )]
        public string $theme = 'light',

        /**
         * User interface language (ISO 639-1 language code)
         * @var string $language
         */
        #[Assert\Choice(
            choices: ['en', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'zh', 'ja'],
            message: 'Invalid language code'
        )]
        public string $language = 'en',

        /**
         * User's timezone for date/time display
         * @var string $timezone
         */
        #[Assert\Choice(
            choices: ['UTC', 'America/New_York', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Tokyo'],
            message: 'Invalid timezone'
        )]
        public string $timezone = 'UTC',

        /**
         * Whether to send email notifications to the user
         * @var bool $emailNotifications
         */
        #[Assert\Type(type: 'boolean', message: 'Email notifications setting must be boolean')]
        public bool $emailNotifications = true,

        /**
         * Whether to send push notifications to the user's devices
         * @var bool $pushNotifications
         */
        #[Assert\Type(type: 'boolean', message: 'Push notifications setting must be boolean')]
        public bool $pushNotifications = true,

        /**
         * Whether to automatically save designs while working
         * @var bool $autoSave
         */
        #[Assert\Type(type: 'boolean', message: 'Auto save setting must be boolean')]
        public bool $autoSave = true,

        /**
         * How often to auto-save in seconds (30-600)
         * @var int $autoSaveInterval
         */
        #[Assert\Type(type: 'integer', message: 'Auto save interval must be an integer')]
        #[Assert\Range(
            min: 30,
            max: 600,
            notInRangeMessage: 'Auto save interval must be between 30 and 600 seconds'
        )]
        public int $autoSaveInterval = 60,

        /**
         * Whether objects automatically snap to grid when moving
         * @var bool $gridSnap
         */
        #[Assert\Type(type: 'boolean', message: 'Grid snap setting must be boolean')]
        public bool $gridSnap = false,

        /**
         * Grid spacing in pixels for snap and alignment (1-100)
         * @var int $gridSize
         */
        #[Assert\Type(type: 'integer', message: 'Grid size must be an integer')]
        #[Assert\Range(
            min: 1,
            max: 100,
            notInRangeMessage: 'Grid size must be between 1 and 100 pixels'
        )]
        public int $gridSize = 10,

        /**
         * Canvas rendering quality level (1=low, 2=medium, 3=high, 4=ultra)
         * @var int $canvasQuality
         */
        #[Assert\Type(type: 'integer', message: 'Canvas quality must be an integer')]
        #[Assert\Range(
            min: 1,
            max: 4,
            notInRangeMessage: 'Canvas quality must be between 1 and 4'
        )]
        public int $canvasQuality = 2
    ) {}

    public function toArray(): array
    {
        return [
            'theme' => $this->theme,
            'language' => $this->language,
            'timezone' => $this->timezone,
            'emailNotifications' => $this->emailNotifications,
            'pushNotifications' => $this->pushNotifications,
            'autoSave' => $this->autoSave,
            'autoSaveInterval' => $this->autoSaveInterval,
            'gridSnap' => $this->gridSnap,
            'gridSize' => $this->gridSize,
            'canvasQuality' => $this->canvasQuality,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            theme: $data['theme'] ?? 'light',
            language: $data['language'] ?? 'en',
            timezone: $data['timezone'] ?? 'UTC',
            emailNotifications: (bool)($data['emailNotifications'] ?? true),
            pushNotifications: (bool)($data['pushNotifications'] ?? true),
            autoSave: (bool)($data['autoSave'] ?? true),
            autoSaveInterval: (int)($data['autoSaveInterval'] ?? 60),
            gridSnap: (bool)($data['gridSnap'] ?? false),
            gridSize: (int)($data['gridSize'] ?? 10),
            canvasQuality: (int)($data['canvasQuality'] ?? 2),
        );
    }
}
