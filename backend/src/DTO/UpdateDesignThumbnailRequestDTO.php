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
         */
        #[Assert\NotBlank(message: 'Thumbnail URL is required')]
        #[Assert\Url(message: 'Thumbnail must be a valid URL')]
        public readonly string $thumbnail,
    ) {}
}
