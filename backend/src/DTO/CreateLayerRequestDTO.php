<?php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\ValueObject\Transform;
use App\DTO\ValueObject\TextLayerProperties;
use App\DTO\ValueObject\ImageLayerProperties;
use App\DTO\ValueObject\ShapeLayerProperties;
use App\DTO\ValueObject\SvgLayerProperties;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for layer creation requests
 * 
 * Handles the creation of new design layers with all necessary properties
 * for proper initialization in the canvas editor. Supports all layer types
 * including text, image, shape, group, video, and audio layers.
 */
final readonly class CreateLayerRequestDTO
{
    public function __construct(
        /**
         * Design ID where the new layer will be created
         * Must be a valid UUID string identifying an existing design
         * Used to ensure layer is added to the correct design context
         */
        #[Assert\NotBlank(message: 'Design ID is required')]
        #[Assert\Type(type: ['int','string'], message: 'Design ID must be a string')]
        public int|string $designId,

        /**
         * Type of layer being created
         * Determines which properties and behaviors the layer will have
         * Valid values: text, image, shape, group, video, audio, svg
         */
        #[Assert\NotBlank(message: 'Layer type is required')]
        #[Assert\Choice(
            choices: ['text', 'image', 'shape', 'group', 'video', 'audio', 'svg'],
            message: 'Invalid layer type. Must be one of: text, image, shape, group, video, audio, svg'
        )]
        public string $type,

        /**
         * Display name for the layer
         * Used for identification in the layers panel and timeline
         * Must be 1-255 characters long
         */
        #[Assert\NotBlank(message: 'Layer name is required')]
        #[Assert\Length(
            max: 255, 
            maxMessage: 'Layer name cannot be longer than 255 characters'
        )]
        public string $name,

        /**
         * Layer-specific visual and behavior properties
         * Contains type-specific attributes like text styling, image src, or shape appearance
         * Structure depends on layer type and is validated accordingly
         * 
         * @var TextLayerProperties|ImageLayerProperties|ShapeLayerProperties|SvgLayerProperties
         */
        public TextLayerProperties|ImageLayerProperties|ShapeLayerProperties|SvgLayerProperties $properties,

        /**
         * 2D transformation matrix for initial layer positioning
         * Controls position, size, rotation, scale, skew, and opacity
         * Defaults to standard position if not provided
         */
        public Transform $transform,

        /**
         * Layer stacking order within the design
         * Higher values appear above lower values in the visual stack
         * Must be zero or positive integer, null for auto-assignment
         */
        #[Assert\Type(type: 'integer', message: 'Z-index must be an integer')]
        #[Assert\PositiveOrZero(message: 'Z-index must be positive or zero')]
        public ?int $zIndex = null,

        /**
         * Initial visibility state of the layer
         * true: Layer is visible and rendered in the canvas (default)
         * false: Layer is hidden from view but remains in the design
         */
        #[Assert\Type(type: 'boolean', message: 'Visible must be a boolean')]
        public ?bool $visible = true,

        /**
         * Initial edit protection state
         * false: Layer can be freely edited and manipulated (default)
         * true: Layer cannot be selected, moved, or modified
         */
        #[Assert\Type(type: 'boolean', message: 'Locked must be a boolean')]
        public ?bool $locked = false,

        /**
         * Parent layer ID for hierarchical grouping
         * null: Layer is created at root level of the design (default)
         * string: Layer is nested under the specified parent layer
         */
        #[Assert\Type(type: 'string', message: 'Parent layer ID must be a string')]
        public ?string $parentLayerId = null,
    ) {}

    /**
     * Convert properties to array for legacy compatibility
     * Returns the properties as an array for systems expecting array format
     */
    public function getPropertiesArray(): array
    {
        return $this->properties->toArray();
    }

    /**
     * Convert transform to array for legacy compatibility
     * Returns the transform as an array for systems expecting array format
     */
    public function getTransformArray(): array
    {
        return $this->transform->toArray();
    }
}
