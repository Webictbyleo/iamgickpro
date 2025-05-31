<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for plugin file upload requests.
 * 
 * Handles validation and encapsulation of plugin file upload data including
 * file type validation, size constraints, and security checks for plugin files.
 * 
 * @author Human Developer
 * @since 1.0.0
 */
readonly class UploadPluginFileRequestDTO
{
    public function __construct(
        #[Assert\NotNull(message: 'Plugin file is required')]
        #[Assert\File(
            maxSize: '50M',
            maxSizeMessage: 'Plugin file size cannot exceed 50MB',
            mimeTypes: [
                'application/zip', 
                'application/x-zip-compressed',
                'application/x-zip'
            ],
            mimeTypesMessage: 'Plugin file must be a valid ZIP archive'
        )]
        public ?UploadedFile $file = null
    ) {}
}
