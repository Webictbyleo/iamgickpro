<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for avatar upload requests.
 * 
 * Handles validation and encapsulation of avatar file upload data including
 * file type validation, size constraints, and security checks.
 * 
 * @author Human Developer
 * @since 1.0.0
 */
readonly class UploadAvatarRequestDTO
{
    public function __construct(
        #[Assert\NotNull(message: 'Avatar file is required')]
        #[Assert\File(
            maxSize: '5M',
            maxSizeMessage: 'Avatar file size cannot exceed 5MB',
            mimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            mimeTypesMessage: 'Avatar must be a valid image file (JPEG, PNG, GIF, or WebP)'
        )]
        public ?UploadedFile $avatar = null
    ) {}
}
