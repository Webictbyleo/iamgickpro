<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for plugin update requests.
 * 
 * Handles partial updates to existing plugins in the platform's
 * plugin system. All fields are optional and null values indicate
 * no change should be made. Used for both plugin developer updates
 * and administrative status changes.
 */
final readonly class UpdatePluginRequestDTO
{
    public function __construct(
        /**
         * Updated display name of the plugin.
         * 
         * If provided, replaces the current plugin name. Must be
         * descriptive and unique. Null indicates no change.
         */
        #[Assert\Length(min: 2, max: 100, minMessage: 'Plugin name must be at least 2 characters long', maxMessage: 'Plugin name cannot exceed 100 characters')]
        public ?string $name = null,

        /**
         * Updated description of the plugin's functionality.
         * 
         * If provided, replaces the current description. Should
         * comprehensively explain the plugin's features and benefits.
         * Null indicates no change.
         */
        #[Assert\Length(min: 10, max: 1000, minMessage: 'Plugin description must be at least 10 characters long', maxMessage: 'Plugin description cannot exceed 1000 characters')]
        public ?string $description = null,

        /**
         * Updated categories for plugin classification.
         * 
         * If provided, replaces the current category set. Categories
         * help users discover plugins through marketplace filtering.
         * Null indicates no change.
         * 
         * @var string[]|null Array of category names or null
         */
        #[Assert\Count(min: 1, max: 5, minMessage: 'At least one category is required', maxMessage: 'Cannot exceed 5 categories')]
        #[Assert\All([
            new Assert\NotBlank(message: 'Category cannot be empty'),
            new Assert\Length(max: 50, maxMessage: 'Category name cannot exceed 50 characters')
        ])]
        public ?array $categories = null,

        /**
         * Updated semantic version number.
         * 
         * If provided, updates the plugin version. Must follow
         * semantic versioning format. Used for update management.
         * Null indicates no change.
         */
        #[Assert\Regex(pattern: '/^\d+\.\d+\.\d+(-[a-zA-Z0-9-]+)?$/', message: 'Version must follow semantic versioning (e.g., 1.0.0)')]
        public ?string $version = null,

        /**
         * Updated permission requirements.
         * 
         * If provided, replaces the current permission set. Each
         * permission grants access to specific platform features.
         * Null indicates no change.
         * 
         * @var string[]|null Array of permission names or null
         */
        #[Assert\All([
            new Assert\Choice(choices: ['editor', 'filesystem', 'network', 'clipboard', 'notifications'], message: 'Invalid permission type')
        ])]
        public ?array $permissions = null,

        /**
         * Updated plugin manifest configuration.
         * 
         * If provided, replaces the current manifest. Contains
         * plugin metadata, entry points, and runtime configuration.
         * Null indicates no change.
         * 
         * @var array<string, mixed>|null Structured manifest data or null
         */
        #[Assert\Type(type: 'array', message: 'Manifest must be a valid array')]
        public ?array $manifest = null,

        /**
         * Updated approval status for the plugin.
         * 
         * If provided, changes the plugin's approval status in the
         * marketplace. Typically used by administrators for plugin
         * review and approval workflows. Null indicates no change.
         * 
         * Available statuses:
         * - 'pending': Awaiting review
         * - 'approved': Available in marketplace
         * - 'rejected': Not approved for marketplace
         */
        #[Assert\Choice(choices: ['pending', 'approved', 'rejected'], message: 'Invalid status. Must be pending, approved, or rejected')]
        public ?string $status = null,
    ) {}
}
