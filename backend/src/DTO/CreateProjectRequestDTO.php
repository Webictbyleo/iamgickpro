<?php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\ValueObject\ProjectSettings;
use App\DTO\ValueObject\Tag;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for creating a new design project.
 * 
 * This DTO handles the creation of new projects with all necessary
 * configuration options, including canvas settings, metadata, and
 * organizational tags.
 */
class CreateProjectRequestDTO
{
    public function __construct(
        /**
         * The display name of the project.
         * 
         * This name is used throughout the application interface
         * and should be descriptive and meaningful to the user.
         * Must be between 1-255 characters.
         */
        #[Assert\NotBlank(message: 'Project name is required')]
        #[Assert\Length(min: 1, max: 255, minMessage: 'Project name must be at least 1 character', maxMessage: 'Project name cannot exceed 255 characters')]
        public readonly string $name,

        /**
         * Optional description providing additional context about the project.
         * 
         * Used to document the project's purpose, goals, or any other
         * relevant information. Maximum 1000 characters to keep descriptions
         * concise but informative.
         */
        #[Assert\Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')]
        public readonly ?string $description = null,

        /**
         * Whether the project should be publicly accessible.
         * 
         * Public projects can be viewed by other users and may appear
         * in community galleries or search results. Private projects
         * are only accessible to the owner and collaborators.
         */
        #[Assert\Type('bool', message: 'Is public must be a boolean')]
        public readonly bool $isPublic = false,

        /**
         * Project configuration settings including canvas dimensions, DPI, etc.
         * 
         * Contains all technical settings that define how the project
         * behaves and renders, including:
         * - Canvas size and background
         * - Export settings (DPI, quality)
         * - Snap and grid configurations
         * - Auto-save preferences
         */
        #[Assert\Valid]
        public readonly ProjectSettings $settings,

        /**
         * Organizational tags for categorizing and searching projects.
         * 
         * Tags help users organize their projects and make them discoverable
         * through search and filtering. Each tag must be 1-50 characters
         * and contain only alphanumeric characters, spaces, hyphens, and underscores.
         * 
         * @var Tag[] $tags Array of validated tag objects
         */
        #[Assert\Valid]
        public readonly array $tags = [],

        /**
         * Optional URL to a thumbnail image representing the project.
         * 
         * Used for project previews in lists and galleries. Should be
         * a valid URL pointing to an image file. If not provided,
         * a thumbnail will be generated from the project content.
         */
        #[Assert\Url(message: 'Thumbnail must be a valid URL')]
        public readonly ?string $thumbnail = null,
    ) {}

    /**
     * Converts the ProjectSettings object to legacy array format.
     * 
     * Provides backward compatibility with existing code that expects
     * array-based settings data.
     * 
     * @return array<string, mixed>
     */
    public function getSettingsArray(): array
    {
        return $this->settings->toArray();
    }

    /**
     * Converts the Tag objects to legacy array format.
     * 
     * Returns an array of tag names for compatibility with existing
     * code that expects string-based tag arrays.
     * 
     * @return string[]
     */
    public function getTagsArray(): array
    {
        return array_map(fn(Tag $tag) => $tag->name, $this->tags);
    }

    /**
     * Creates Tag objects from an array of tag names.
     * 
     * Factory method for creating validated Tag objects from
     * string-based input data.
     * 
     * @param string[] $tagNames
     * @return Tag[]
     */
    public static function createTagsFromStrings(array $tagNames): array
    {
        return array_map(fn(string $name) => new Tag($name), $tagNames);
    }
}
