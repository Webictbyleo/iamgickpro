<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for project duplication requests.
 * 
 * Handles the creation of a complete copy of an existing project
 * including all associated designs, layers, and metadata. Used
 * in the project management system to allow users to quickly
 * create new projects based on existing templates.
 */
readonly class DuplicateProjectRequestDTO
{
    public function __construct(
        /**
         * Display name for the duplicated project.
         * 
         * The name given to the new project copy. Must be between
         * 1-255 characters and will be displayed in the user's
         * project list. If null, a default name will be generated
         * based on the original project name with a copy suffix.
         */
        #[Assert\Length(
            min: 1,
            max: 255,
            minMessage: 'Project name must be at least {{ limit }} characters long',
            maxMessage: 'Project name cannot be longer than {{ limit }} characters'
        )]
        public ?string $name = null,
    ) {
    }
}
