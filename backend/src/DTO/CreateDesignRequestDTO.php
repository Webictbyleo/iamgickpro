<?php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\ValueObject\DesignData;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for creating a new design within a project.
 * 
 * This DTO handles the creation of new designs with specified canvas
 * dimensions, initial design settings, and organizational metadata.
 */
class CreateDesignRequestDTO
{
    public function __construct(
        /**
         * Display name for the new design.
         * 
         * This name is used throughout the application interface
         * and should be descriptive and meaningful to the user.
         * Must be between 1-255 characters.
         */
        #[Assert\NotBlank(message: 'Design name is required')]
        #[Assert\Length(min: 1, max: 255, minMessage: 'Design name must be at least 1 character', maxMessage: 'Design name cannot exceed 255 characters')]
        public readonly string $name,

        /**
         * Optional description providing additional context about the design.
         * 
         * Used to document the design's purpose, goals, or any other
         * relevant information. Maximum 1000 characters to keep descriptions
         * concise but informative.
         */
        #[Assert\Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')]
        public readonly ?string $description = null,

        /**
         * Design-level configuration and settings.
         * 
         * Contains global design settings including canvas background,
         * animation configuration, grid settings, view preferences,
         * and custom styling options that apply to the entire design.
         * 
         * Defaults to empty configuration if not provided.
         */
        #[Assert\Valid]
        public readonly DesignData $data = new DesignData(),

        /**
         * Optional project ID to associate the design with.
         * 
         * If provided, the design will be created within the specified
         * project for organization and collaboration purposes. If null,
         * the design will be created as a standalone item.
         */
        #[Assert\Type('integer', message: 'Project ID must be an integer')]
        #[Assert\Positive(message: 'Project ID must be positive')]
        public readonly ?int $projectId = null,

        /**
         * Canvas width in pixels.
         * 
         * Defines the horizontal dimension of the design canvas.
         * Must be between 1-10000 pixels for reasonable performance
         * and export capabilities. Defaults to 1920px (Full HD width).
         */
        #[Assert\Type('integer', message: 'Width must be an integer')]
        #[Assert\Positive(message: 'Width must be positive')]
        #[Assert\Range(min: 1, max: 10000, notInRangeMessage: 'Width must be between 1 and 10000 pixels')]
        public readonly int $width = 1920,

        /**
         * Canvas height in pixels.
         * 
         * Defines the vertical dimension of the design canvas.
         * Must be between 1-10000 pixels for reasonable performance
         * and export capabilities. Defaults to 1080px (Full HD height).
         */
        #[Assert\Type('integer', message: 'Height must be an integer')]
        #[Assert\Positive(message: 'Height must be positive')]
        #[Assert\Range(min: 1, max: 10000, notInRangeMessage: 'Height must be between 1 and 10000 pixels')]
        public readonly int $height = 1080,

        /**
         * Whether the design should be publicly accessible.
         * 
         * Public designs can be viewed by other users and may appear
         * in community galleries or search results. Private designs
         * are only accessible to the owner and collaborators.
         */
        #[Assert\Type('bool', message: 'Is public must be a boolean')]
        public readonly bool $isPublic = false,
    ) {}

    /**
     * Checks if a project ID is provided.
     * 
     * Returns true if the design should be associated with a project,
     * false if it should be created as a standalone design.
     * 
     * @return bool True if project ID is set
     */
    public function hasProjectId(): bool
    {
        return $this->projectId !== null;
    }

    /**
     * Converts the DesignData object to legacy array format.
     * 
     * Provides backward compatibility with existing code that expects
     * array-based design data.
     * 
     * @return array<string, mixed>
     */
    public function getDataArray(): array
    {
        return $this->data->toArray();
    }
}
