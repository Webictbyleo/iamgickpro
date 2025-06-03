<?php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\ValueObject\MediaMetadata;
use App\DTO\ValueObject\Tag;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for creating a new media item in the library.
 * 
 * This DTO handles the creation of media assets including images, videos,
 * and audio files from various sources (uploads, stock photo APIs, etc.).
 * Includes comprehensive metadata and organizational features.
 */
readonly class CreateMediaRequestDTO
{
    public function __construct(
        /**
         * Display name for the media item.
         * 
         * This name is shown in the media library and used for searching.
         * Should be descriptive and meaningful to help users identify
         * the content. Must be between 1-255 characters.
         */
        #[Assert\NotBlank(message: 'Media name is required')]
        #[Assert\Length(
            min: 1,
            max: 255,
            minMessage: 'Media name must be at least {{ limit }} character long',
            maxMessage: 'Media name cannot be longer than {{ limit }} characters'
        )]
        public string $name,

        /**
         * MIME type of the media file.
         * 
         * Specifies the exact format of the media file for proper
         * handling by browsers and processing tools. Examples:
         * - image/jpeg, image/png, image/svg+xml
         * - video/mp4, video/webm
         * - audio/mpeg, audio/wav
         */
        #[Assert\NotBlank(message: 'MIME type is required')]
        public string $mimeType,

        /**
         * File size in bytes.
         * 
         * Used for storage quota management, upload progress,
         * and performance optimization decisions. Must be a
         * positive number representing the file size in bytes.
         */
        #[Assert\PositiveOrZero(message: 'Size must be a positive number')]
        public int $size,

        /**
         * Direct URL to access the media file.
         * 
         * This is the primary URL used to display or download the media.
         * Should be a publicly accessible HTTPS URL for security and
         * browser compatibility.
         */
        #[Assert\NotBlank(message: 'URL is required')]
        #[Assert\Url(message: 'URL must be a valid URL')]
        public string $url,

        /**
         * Type of media content.
         * 
         * Categorizes the media into broad types for filtering and
         * appropriate handling in the editor. Supported types:
         * - image: Static images (JPEG, PNG, SVG, etc.)
         * - video: Video files (MP4, WebM, etc.)
         * - audio: Audio files (MP3, WAV, etc.)
         */
        #[Assert\NotBlank(message: 'Media type is required')]
        #[Assert\Choice(
            choices: ['image', 'video', 'audio'],
            message: 'Type must be one of: image, video, audio'
        )]
        public string $type = 'image',

        /**
         * Optional URL to a thumbnail or preview image.
         * 
         * Used for quick previews in the media library and layer
         * thumbnails. For videos, this could be a frame capture.
         * For audio, this could be a waveform visualization.
         */
        #[Assert\Url(message: 'Thumbnail URL must be a valid URL')]
        public ?string $thumbnailUrl = null,

        /**
         * Width of the media in pixels (for visual media).
         * 
         * Essential for layout calculations and aspect ratio
         * preservation. Not applicable for audio files.
         */
        #[Assert\PositiveOrZero(message: 'Width must be a positive number')]
        public ?int $width = null,

        /**
         * Height of the media in pixels (for visual media).
         * 
         * Essential for layout calculations and aspect ratio
         * preservation. Not applicable for audio files.
         */
        #[Assert\PositiveOrZero(message: 'Height must be a positive number')]
        public ?int $height = null,

        /**
         * Duration in seconds (for time-based media).
         * 
         * Used for video and audio files to display playback
         * length and for timeline-based editing features.
         * Not applicable for static images.
         */
        #[Assert\PositiveOrZero(message: 'Duration must be a positive number')]
        public ?float $duration = null,

        /**
         * Source platform or service where the media originated.
         * 
         * Tracks the origin of media for attribution, licensing,
         * and integration purposes. Supported sources:
         * - upload: User-uploaded content
         * - unsplash: Unsplash stock photos
         * - pexels: Pexels stock photos
         * - pixabay: Pixabay stock media
         */

        #[Assert\Choice(
            choices: ['upload', 'unsplash', 'pexels', 'pixabay'],
            message: 'Source must be one of: upload, unsplash, pexels, pixabay'
        )]
        public string $source = 'upload',

        /**
         * Unique identifier from the source platform.
         * 
         * For stock photo services, this is their internal ID for the media.
         * For uploads, this may be null or a user-defined reference.
         * Used for attribution and preventing duplicate imports.
         */
        public ?string $sourceId = null,

        /**
         * Technical metadata about the media file.
         * 
         * Contains detailed information about the media file including
         * file size, MIME type, dimensions, and format-specific data
         * like codec information for videos or EXIF data for images.
         * 
         * Used for display, processing, and compatibility checks.
         */
        #[Assert\Valid]
        public ?MediaMetadata $metadata = null,

        /**
         * Organizational tags for categorizing and searching media.
         * 
         * Tags help users organize their media library and make content
         * discoverable through search and filtering. Each tag must be
         * 1-50 characters and contain only alphanumeric characters,
         * spaces, hyphens, and underscores.
         * 
         * @var Tag[] $tags Array of validated tag objects
         */
        #[Assert\Valid]
        public ?array $tags = null,

        /**
         * Attribution text for the media creator.
         * 
         * Required for some stock photo services and user-generated content.
         * Displayed in media details and export metadata to comply with
         * licensing requirements.
         */
        public ?string $attribution = null,

        /**
         * License type under which the media is distributed.
         * 
         * Defines usage rights and restrictions for the media.
         * Common values include 'CC0', 'CC BY', 'Commercial', 'Editorial'.
         * Used to ensure proper usage compliance.
         */
        public ?string $license = null,

        /**
         * Whether this media requires a premium subscription to use.
         * 
         * Premium media may have additional licensing costs or
         * require special subscription tiers. Affects availability
         * and usage tracking.
         */
        #[Assert\Type('bool', message: 'isPremium must be a boolean')]
        public bool $isPremium = false,

        /**
         * Whether this media is currently active and available for use.
         * 
         * Inactive media is hidden from search results and cannot be
         * used in new designs. Existing usages remain functional.
         * Used for content moderation and lifecycle management.
         */
        #[Assert\Type('bool', message: 'isActive must be a boolean')]
        public bool $isActive = true,
    ) {
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
