<?php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\ValueObject\DesignData;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for updating an existing design.
 * 
 * This DTO handles partial updates to designs, allowing clients to
 * update only the fields they want to change. All fields are optional
 * and null values indicate no change should be made.
 */
class UpdateDesignRequestDTO
{
    public function __construct(
        /**
         * Updated display name for the design.
         * 
         * If provided, replaces the current design name. Must be
         * between 1-255 characters. Null indicates no change.
         */
        #[Assert\Length(min: 1, max: 255, minMessage: 'Design name must be at least 1 character', maxMessage: 'Design name cannot exceed 255 characters')]
        public readonly ?string $name = null,

        /**
         * Updated description for the design.
         * 
         * If provided, replaces the current description. Maximum
         * 1000 characters. Null indicates no change.
         */
        #[Assert\Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')]
        public readonly ?string $description = null,

        /**
         * Updated design-level configuration and settings.
         * 
         * If provided, merges with or replaces current design settings
         * including canvas background, animations, grid settings, etc.
         * Null indicates no change to design data.
         */
        #[Assert\Valid]
        public readonly ?DesignData $data = null,

        /**
         * Updated project association for the design.
         * 
         * If provided, moves the design to the specified project.
         * Can be used to move designs between projects or remove
         * from project (if supported). Null indicates no change.
         */
        #[Assert\Type('integer', message: 'Project ID must be an integer')]
        #[Assert\Positive(message: 'Project ID must be positive')]
        public readonly ?int $projectId = null,

        /**
         * Updated canvas width in pixels.
         * 
         * If provided, resizes the canvas width. Must be between
         * 1-10000 pixels. This may affect layer positioning.
         * Null indicates no change.
         */
        #[Assert\Type('integer', message: 'Width must be an integer')]
        #[Assert\Positive(message: 'Width must be positive')]
        #[Assert\Range(min: 1, max: 10000, notInRangeMessage: 'Width must be between 1 and 10000 pixels')]
        public readonly ?int $width = null,

        /**
         * Updated canvas height in pixels.
         * 
         * If provided, resizes the canvas height. Must be between
         * 1-10000 pixels. This may affect layer positioning.
         * Null indicates no change.
         */
        #[Assert\Type('integer', message: 'Height must be an integer')]
        #[Assert\Positive(message: 'Height must be positive')]
        #[Assert\Range(min: 1, max: 10000, notInRangeMessage: 'Height must be between 1 and 10000 pixels')]
        public readonly ?int $height = null,

        /**
         * Updated public visibility for the design.
         * 
         * If provided, changes whether the design is publicly accessible
         * and can appear in community galleries. Null indicates no change.
         */
        #[Assert\Type('bool', message: 'Is public must be a boolean')]
        public readonly ?bool $isPublic = null,
    ) {}

    /**
     * Checks if any update data is provided.
     * 
     * Returns true if at least one field has a non-null value,
     * indicating that an update should be performed.
     * 
     * @return bool True if any update data is present
     */
    public function hasAnyData(): bool
    {
        return $this->name !== null
            || $this->description !== null
            || $this->data !== null
            || $this->projectId !== null
            || $this->width !== null
            || $this->height !== null
            || $this->isPublic !== null;
    }

    /**
     * Converts the DesignData object to legacy array format.
     * 
     * Provides backward compatibility with existing code that expects
     * array-based design data.
     * 
     * @return array<string, mixed>|null
     */
    public function getDataArray(): ?array
    {
        return $this->data?->toArray();
    }
}
