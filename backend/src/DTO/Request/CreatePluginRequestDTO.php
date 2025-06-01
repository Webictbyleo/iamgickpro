<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for plugin creation requests.
 * 
 * Handles the submission of new plugins to the platform's plugin
 * system. Contains all necessary metadata and configuration for
 * plugin registration, validation, and eventual approval process.
 * Used in the plugin management system to onboard new extensions.
 */
final readonly class CreatePluginRequestDTO
{
    public function __construct(
        /**
         * Display name of the plugin.
         * 
         * Human-readable name shown in the plugin marketplace
         * and installation interface. Must be descriptive and
         * unique to help users identify the plugin's purpose.
         */
        #[Assert\NotBlank(message: 'Plugin name is required')]
        #[Assert\Length(min: 2, max: 100, minMessage: 'Plugin name must be at least 2 characters long', maxMessage: 'Plugin name cannot exceed 100 characters')]
        public string $name,

        /**
         * Detailed description of the plugin's functionality.
         * 
         * Comprehensive explanation of what the plugin does,
         * its features, and how users can benefit from it.
         * Displayed in the plugin marketplace and during installation.
         */
        #[Assert\NotBlank(message: 'Plugin description is required')]
        #[Assert\Length(min: 10, max: 1000, minMessage: 'Plugin description must be at least 10 characters long', maxMessage: 'Plugin description cannot exceed 1000 characters')]
        public string $description,

        /**
         * Categories that classify the plugin's functionality.
         * 
         * Array of category names that help users discover the plugin
         * through marketplace browsing and filtering. Common categories
         * include 'Design Tools', 'Export', 'Templates', 'Media', etc.
         * 
         * @var string[] Array of category names
         */
        #[Assert\NotBlank(message: 'Categories are required')]
        #[Assert\Count(min: 1, max: 5, minMessage: 'At least one category is required', maxMessage: 'Cannot exceed 5 categories')]
        #[Assert\All([
            new Assert\NotBlank(message: 'Category cannot be empty'),
            new Assert\Length(max: 50, maxMessage: 'Category name cannot exceed 50 characters')
        ])]
        public array $categories,

        /**
         * Semantic version number of the plugin.
         * 
         * Version identifier following semantic versioning (semver)
         * format (e.g., "1.0.0" or "2.1.3-beta"). Used for update
         * management and compatibility checking.
         */
        #[Assert\NotBlank(message: 'Plugin version is required')]
        #[Assert\Regex(pattern: '/^\d+\.\d+\.\d+(-[a-zA-Z0-9-]+)?$/', message: 'Version must follow semantic versioning (e.g., 1.0.0)')]
        public string $version,

        /**
         * Required permissions for the plugin to function.
         * 
         * Array of permission types that the plugin needs to access
         * platform features. Used for security validation and to
         * inform users about what the plugin can access.
         * 
         * Available permissions:
         * - 'editor': Access to design editor APIs
         * - 'filesystem': File system read/write access
         * - 'network': Network/HTTP request capabilities
         * - 'clipboard': Clipboard read/write access
         * - 'notifications': Push notification capabilities
         * 
         * @var string[] Array of permission names
         */
        #[Assert\NotBlank(message: 'Permissions are required')]
        #[Assert\Count(min: 1, minMessage: 'At least one permission is required')]
        #[Assert\All([
            new Assert\Choice(choices: ['editor', 'filesystem', 'network', 'clipboard', 'notifications'], message: 'Invalid permission type')
        ])]
        public array $permissions,

        /**
         * Plugin manifest configuration.
         * 
         * JSON-formatted configuration containing plugin metadata,
         * entry points, dependencies, and runtime configuration.
         * Defines how the plugin integrates with the platform.
         * 
         * @var array<string, mixed> Structured manifest data
         */
        #[Assert\NotBlank(message: 'Plugin manifest is required')]
        #[Assert\Type(type: 'array', message: 'Manifest must be a valid array')]
        public array $manifest,
    ) {}
}
