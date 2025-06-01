<?php

declare(strict_types=1);

namespace App\DTO\Request;

use App\DTO\ValueObject\Tag;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateTemplateRequestDTO
{
    public function __construct(
        /**
         * The template name (required, max 255 characters)
         * Used for identification and search purposes
         */
        #[Assert\NotBlank(message: 'Template name is required')]
        #[Assert\Length(max: 255, maxMessage: 'Template name cannot be longer than 255 characters')]
        public string $name,

        /**
         * Optional description of the template (max 1000 characters)
         * Provides context about the template's purpose and usage
         */
        #[Assert\Length(max: 1000, maxMessage: 'Description cannot be longer than 1000 characters')]
        public ?string $description = null,

        /**
         * Template category for organization and filtering
         * Must be one of the predefined categories
         */
        #[Assert\NotBlank(message: 'Category is required')]
        #[Assert\Choice(choices: ['social-media', 'presentation', 'print', 'marketing', 'document', 'logo', 'web-graphics', 'video', 'animation'], message: 'Invalid category')]
        public string $category = 'social-media',

        /**
         * Array of tags for categorization and search
         * Each tag must be 1-50 characters and contain only alphanumeric characters, spaces, hyphens, and underscores
         * @var Tag[]
         */
        #[Assert\Type(type: 'array', message: 'Tags must be an array')]
        #[Assert\Valid]
        public array $tags = [],

        /**
         * Canvas width in pixels (required, must be positive)
         * Defines the template's design area width
         */
        #[Assert\NotBlank(message: 'Width is required')]
        #[Assert\Type(type: 'integer', message: 'Width must be an integer')]
        #[Assert\Positive(message: 'Width must be positive')]
        public int $width,

        /**
         * Canvas height in pixels (required, must be positive)
         * Defines the template's design area height
         */
        #[Assert\NotBlank(message: 'Height is required')]
        #[Assert\Type(type: 'integer', message: 'Height must be an integer')]
        #[Assert\Positive(message: 'Height must be positive')]
        public int $height,

        /**
         * Canvas configuration settings as key-value pairs
         * Contains background color, grid settings, guides, etc.
         */
        #[Assert\Type(type: 'array', message: 'Canvas settings must be an array')]
        public array $canvasSettings = [],

        /**
         * Layer definitions for the template
         * Contains the visual elements that make up the template
         */
        #[Assert\Type(type: 'array', message: 'Layers must be an array')]
        public array $layers = [],

        /**
         * Optional URL to template thumbnail image
         * Used for preview in template galleries
         */
        #[Assert\Url(message: 'Thumbnail URL must be a valid URL')]
        public ?string $thumbnailUrl = null,

        /**
         * Optional URL to template preview image
         * Used for larger preview displays
         */
        #[Assert\Url(message: 'Preview URL must be a valid URL')]
        public ?string $previewUrl = null,

        /**
         * Whether this template requires premium access
         * Premium templates are only available to paid users
         */
        #[Assert\Type(type: 'bool', message: 'isPremium must be a boolean')]
        public bool $isPremium = false,

        /**
         * Whether this template is active and visible
         * Inactive templates are hidden from users
         */
        #[Assert\Type(type: 'bool', message: 'isActive must be a boolean')]
        public bool $isActive = true,
    ) {
    }

    /**
     * Get tags as an array of strings for entity persistence
     * 
     * @return string[]
     */
    public function getTagsArray(): array
    {
        return array_map(fn(Tag $tag) => $tag->name, $this->tags);
    }
}
