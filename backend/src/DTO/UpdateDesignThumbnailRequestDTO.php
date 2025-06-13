<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for design thumbnail update requests.
 * 
 * Handles updating the thumbnail image for an existing design.
 * Used when users want to change the preview image that represents
 * their design in galleries, project lists, and search results.
 */
class UpdateDesignThumbnailRequestDTO
{
    public function __construct(
        /**
         * URL of the new thumbnail image.
         * 
         * Must be a valid URL pointing to an image file that will
         * serve as the design's preview thumbnail. The image should
         * be optimized for display in lists and galleries, typically
         * in common web formats (PNG, JPEG, WebP).
         * 
         * Supports both HTTP/HTTPS URLs and data URLs (base64 encoded images).
         */
        #[Assert\NotBlank(message: 'Thumbnail URL is required')]
        #[Assert\Regex(
            pattern: '/^(https?:\/\/.*\.(jpg|jpeg|png|gif|webp|svg)(\?.*)?$)|(data:image\/(jpeg|jpg|png|gif|webp|svg\+xml);base64,[A-Za-z0-9+\/=]+)$/i',
            message: 'Thumbnail must be a valid URL or data URL pointing to an image'
        )]
        public readonly string $thumbnail,
    ) {}
}
