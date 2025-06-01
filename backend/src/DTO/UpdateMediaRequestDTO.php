<?php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\ValueObject\MediaMetadata;
use App\DTO\ValueObject\Tag;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for updating an existing media item.
 * 
 * This DTO handles partial updates to media assets, allowing clients to
 * update only the fields they want to change. All fields are optional
 * and null values indicate no change should be made.
 */
class UpdateMediaRequestDTO
{
    public function __construct(
        /**
         * Updated display name for the media item.
         * 
         * If provided, replaces the current media name. Must be
         * between 1-255 characters. Null indicates no change.
         */
        #[Assert\Length(min: 1, max: 255, minMessage: 'File name must be at least 1 character', maxMessage: 'File name cannot exceed 255 characters')]
        public readonly ?string $name = null,

        /**
         * Updated description for the media item.
         * 
         * If provided, replaces the current description. Maximum
         * 1000 characters. Null indicates no change.
         */
        #[Assert\Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')]
        public readonly ?string $description = null,

        /**
         * Updated organizational tags for the media.
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
         * Updated technical metadata for the media file.
         * 
         * If provided, replaces or merges with current metadata.
         * Contains detailed information about the media file including
         * dimensions, codec info, EXIF data, etc. Null indicates no change.
         */
        #[Assert\Valid]
        public readonly ?MediaMetadata $metadata = null,

        /**
         * Updated premium status for the media.
         * 
         * If provided, changes whether the media requires a premium
         * subscription to use. Null indicates no change.
         */
        #[Assert\Type('bool', message: 'Is premium must be a boolean')]
        public readonly ?bool $isPremium = null,

        /**
         * Updated active status for the media.
         * 
         * If provided, changes whether the media is currently available
         * for use in new designs. Null indicates no change.
         */
        #[Assert\Type('bool', message: 'Is active must be a boolean')]
        public readonly ?bool $isActive = null,

        /**
         * Updated public visibility for the media.
         * 
         * If provided, changes whether the media is publicly accessible
         * and can appear in community galleries. Null indicates no change.
         */
        #[Assert\Type('bool', message: 'Is public must be a boolean')]
        public readonly ?bool $isPublic = null,
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
            || $this->tags !== null
            || $this->metadata !== null
            || $this->isPremium !== null
            || $this->isActive !== null
            || $this->isPublic !== null;
    }

    /**
     * Converts the MediaMetadata object to legacy array format.
     * 
     * Provides backward compatibility with existing code that expects
     * array-based metadata.
     * 
     * @return array<string, mixed>|null
     */
    public function getMetadataArray(): ?array
    {
        return $this->metadata?->toArray();
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
