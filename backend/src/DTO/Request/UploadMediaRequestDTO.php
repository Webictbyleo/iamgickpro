<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for media file upload requests.
 * 
 * Simplified DTO that only accepts the file being uploaded and an optional name.
 * All other metadata (size, dimensions, MIME type, etc.) is automatically 
 * extracted from the file by the MediaService.
 * 
 * @author Human Developer
 * @since 1.0.0
 */
readonly class UploadMediaRequestDTO
{
    public function __construct(
        /**
         * The uploaded media file.
         * 
         * Must be a valid media file (image, video, or audio) within size limits.
         * File type and size are validated to ensure compatibility and security.
         */
        #[Assert\NotNull(message: 'Media file is required')]
        #[Assert\File(
            maxSize: '100M',
            maxSizeMessage: 'Media file size cannot exceed 100MB',
            mimeTypes: [
                // Image formats
                'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
                // Video formats
                'video/mp4', 'video/webm', 'video/ogg', 'video/avi', 'video/mov', 'video/wmv',
                // Audio formats
                'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/aac', 'audio/mp3'
            ],
            mimeTypesMessage: 'Media file must be a valid image, video, or audio file'
        )]
        public ?UploadedFile $file = null,

        /**
         * Optional display name for the media file.
         * 
         * If not provided, the original filename will be used.
         * Must be between 1-255 characters if provided.
         */
        #[Assert\Length(
            min: 1,
            max: 255,
            minMessage: 'Media name must be at least {{ limit }} character long',
            maxMessage: 'Media name cannot be longer than {{ limit }} characters'
        )]
        public ?string $name = null
    ) {}
}
