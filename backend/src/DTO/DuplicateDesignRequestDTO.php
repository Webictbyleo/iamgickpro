<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for design duplication requests.
 * 
 * Handles the duplication of complete designs including all layers,
 * settings, and metadata. Used by the design management system to
 * create copies of existing designs with optional customization
 * of the duplicate's name and target project.
 */
class DuplicateDesignRequestDTO
{
    public function __construct(
        /**
         * Custom name for the duplicated design.
         * 
         * If provided, this will be used as the name for the new design.
         * If null, the system will automatically generate a name like
         * "Copy of {original name}". Must be between 1-255 characters
         * if provided.
         */
        #[Assert\Length(min: 1, max: 255, minMessage: 'Design name must be at least 1 character', maxMessage: 'Design name cannot exceed 255 characters')]
        public readonly ?string $name = null,

        /**
         * Target project ID for the duplicated design.
         * 
         * If provided, the duplicated design will be placed in the
         * specified project. The user must have write access to the
         * target project. If null, the design is duplicated to the
         * same project as the original or as a standalone design.
         */
        #[Assert\Type('integer', message: 'Project ID must be an integer')]
        #[Assert\Positive(message: 'Project ID must be positive')]
        public readonly ?int $projectId = null,
    ) {}
}
