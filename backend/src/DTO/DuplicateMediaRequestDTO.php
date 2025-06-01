<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for media file duplication requests.
 * 
 * Handles the duplication of media files including images, videos,
 * and other assets. Creates a personal copy of accessible media
 * files for the authenticated user with optional name customization.
 * Used by the media library for creating user-owned copies of media.
 */
readonly class DuplicateMediaRequestDTO
{
    public function __construct(
        /**
         * Custom name for the duplicated media file.
         * 
         * If provided, this will be used as the name for the duplicated
         * media file. If null, the system will automatically generate
         * a name like "Copy of {original name}". Must not exceed 255
         * characters if provided.
         */
        #[Assert\Length(
            max: 255,
            maxMessage: 'Media name cannot be longer than {{ limit }} characters'
        )]
        public ?string $name = null,
    ) {
    }
}
