<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ConvertDesignToTemplateRequestDTO
{
    public function __construct(
        /**
         * Template category for organization and filtering
         * Can be any string with allowed characters
         */
        #[Assert\NotBlank(message: 'Category is required')]
        #[Assert\Length(max: 50, maxMessage: 'Category cannot be longer than 50 characters')]
        #[Assert\Regex(pattern: '/^[a-zA-Z0-9\s\-_]+$/', message: 'Category can only contain letters, numbers, spaces, hyphens, and underscores')]
        public string $category,

        /**
         * The template name (optional, will use design name if not provided)
         * Used for identification and search purposes
         */
        #[Assert\Length(max: 255, maxMessage: 'Template name cannot be longer than 255 characters')]
        public ?string $name = null,

        /**
         * Optional description of the template (max 1000 characters)
         * Provides context about the template's purpose and usage
         */
        #[Assert\Length(max: 1000, maxMessage: 'Description cannot be longer than 1000 characters')]
        public ?string $description = null,

        /**
         * Array of tags for categorization and search
         * Each tag should be a simple string
         */
        #[Assert\Type(type: 'array', message: 'Tags must be an array')]
        #[Assert\All([
            new Assert\Type(type: 'string', message: 'Each tag must be a string'),
            new Assert\Length(max: 50, maxMessage: 'Each tag cannot be longer than 50 characters'),
            new Assert\Regex(pattern: '/^[a-zA-Z0-9\s\-_]+$/', message: 'Tags can only contain letters, numbers, spaces, hyphens, and underscores')
        ])]
        public array $tags = [],

        /**
         * Whether the template should be marked as premium
         */
        #[Assert\Type(type: 'bool', message: 'Premium flag must be a boolean')]
        public bool $isPremium = false,

        /**
         * Whether the template should be active/visible
         */
        #[Assert\Type(type: 'bool', message: 'Active flag must be a boolean')]
        public bool $isActive = true,
    ) {}

    /**
     * Convert request data to array for template creation
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'tags' => $this->tags,
            'is_premium' => $this->isPremium,
            'is_active' => $this->isActive,
        ];
    }
}
