<?php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\ValueObject\Tag;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for media file upload requests
 * 
 * Handles the upload of media files (images, videos, audio, documents) 
 * with metadata, categorization tags, and privacy settings.
 * Used by the media upload API endpoint for file processing and storage.
 */
final readonly class UploadMediaRequestDTO
{
    public function __construct(
        /**
         * Display name for the media file
         * Used for file identification and search within the media library
         * Must be 1-255 characters long
         */
        #[Assert\NotBlank(message: 'File name is required')]
        #[Assert\Length(
            min: 1, 
            max: 255, 
            minMessage: 'File name must be at least 1 character', 
            maxMessage: 'File name cannot exceed 255 characters'
        )]
        public string $name,

        /**
         * Media file type category for proper handling and filtering
         * Determines how the file is processed and where it appears in the media library
         * Valid values: image, video, audio, document
         */
        #[Assert\NotBlank(message: 'File type is required')]
        #[Assert\Choice(
            choices: ['image', 'video', 'audio', 'document'], 
            message: 'Invalid file type. Must be one of: image, video, audio, document'
        )]
        public string $type,

        /**
         * Optional description of the media file content
         * Used for accessibility, SEO, and content organization
         * Maximum 1000 characters to provide detailed context
         */
        #[Assert\Length(
            max: 1000, 
            maxMessage: 'Description cannot exceed 1000 characters'
        )]
        public ?string $description = null,

        /**
         * Array of categorization tags for content organization and search
         * Each tag must be 1-50 characters and contain only letters, numbers, spaces, hyphens, underscores
         * Used for filtering and discovering media in the library
         * 
         * @var Tag[]
         */
        #[Assert\Type('array', message: 'Tags must be an array')]
        #[Assert\All([
            new Assert\Type(type: Tag::class, message: 'Each tag must be a valid Tag object')
        ])]
        public array $tags = [],

        /**
         * Privacy setting determining media visibility and sharing permissions
         * true: Media can be shared and used by other users in the platform
         * false: Media is private to the uploading user only
         */
        #[Assert\Type('bool', message: 'Is public must be a boolean')]
        public bool $isPublic = false,
    ) {}

    /**
     * Convert tags to array of strings for legacy compatibility
     * Returns array of tag names for systems that expect string arrays
     */
    public function getTagNames(): array
    {
        return array_map(fn(Tag $tag) => $tag->name, $this->tags);
    }

    /**
     * Create tags array from string array
     * Utility method for converting legacy tag arrays to typed Tag objects
     */
    public static function createTagsFromStrings(array $tagNames): array
    {
        return array_map(fn(string $name) => new Tag($name), $tagNames);
    }
}
