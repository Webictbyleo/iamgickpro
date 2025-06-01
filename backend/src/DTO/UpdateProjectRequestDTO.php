<?php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\ValueObject\ProjectSettings;
use App\DTO\ValueObject\Tag;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for updating an existing design project.
 * 
 * This DTO handles partial updates to projects, allowing clients to
 * update only the fields they want to change. All fields are optional
 * and null values indicate no change should be made.
 */
class UpdateProjectRequestDTO
{
    public function __construct(
        /**
         * Updated display name for the project.
         * 
         * If provided, replaces the current project name. Must be
         * between 1-255 characters. Null indicates no change.
         */
        #[Assert\Length(min: 1, max: 255, minMessage: 'Project name must be at least 1 character', maxMessage: 'Project name cannot exceed 255 characters')]
        public readonly ?string $name = null,

        /**
         * Updated description for the project.
         * 
         * If provided, replaces the current description. Maximum
         * 1000 characters. Null indicates no change.
         */
        #[Assert\Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')]
        public readonly ?string $description = null,

        /**
         * Updated visibility setting for the project.
         * 
         * If provided, changes whether the project is publicly
         * accessible. Null indicates no change.
         */
        #[Assert\Type('bool', message: 'Is public must be a boolean')]
        public readonly ?bool $isPublic = null,

        /**
         * Updated project configuration settings.
         * 
         * If provided, replaces or merges with current project settings
         * including canvas dimensions, DPI, export preferences, etc.
         * Null indicates no change to settings.
         */
        #[Assert\Valid]
        public readonly ?ProjectSettings $settings = null,

        /**
         * Updated organizational tags for the project.
         * 
         * If provided, replaces the current tag set. Each tag must be
         * 1-50 characters and contain only alphanumeric characters,
         * spaces, hyphens, and underscores. Null indicates no change.
         * 
         * @var Tag[]|null $tags Array of validated tag objects or null
         */
        #[Assert\Valid]
        public readonly ?array $tags = null,

        /**
         * Updated thumbnail URL for the project.
         * 
         * If provided, replaces the current thumbnail. Should be a
         * valid URL pointing to an image file. Null indicates no change.
         */
        #[Assert\Url(message: 'Thumbnail must be a valid URL')]
        public readonly ?string $thumbnail = null,
    ) {}

    /**
     * Checks if any update data is provided.
     * 
     * Returns true if at least one field has a non-null value,
     * indicating that an update should be performed.
     * 
     * @return bool True if any update data is present
     */
    public function hasAnyData(): bool
    {
        return $this->name !== null
            || $this->description !== null
            || $this->isPublic !== null
            || $this->settings !== null
            || $this->tags !== null
            || $this->thumbnail !== null;
    }

    /**
     * Converts the ProjectSettings object to legacy array format.
     * 
     * Provides backward compatibility with existing code that expects
     * array-based settings data.
     * 
     * @return array<string, mixed>|null
     */
    public function getSettingsArray(): ?array
    {
        return $this->settings?->toArray();
    }

    /**
     * Converts the Tag objects to legacy array format.
     * 
     * Returns an array of tag names for compatibility with existing
     * code that expects string-based tag arrays.
     * 
     * @return string[]|null
     */
    public function getTagsArray(): ?array
    {
        return $this->tags ? array_map(fn(Tag $tag) => $tag->name, $this->tags) : null;
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
