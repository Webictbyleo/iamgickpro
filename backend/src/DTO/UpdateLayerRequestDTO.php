<?php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\ValueObject\Transform;
use App\DTO\ValueObject\LayerProperties;
use App\DTO\ValueObject\TextLayerProperties;
use App\DTO\ValueObject\ImageLayerProperties;
use App\DTO\ValueObject\ShapeLayerProperties;
use App\DTO\ValueObject\SvgLayerProperties;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for layer update requests
 * 
 * Handles modification of existing design layers including visual properties,
 * positioning, visibility, and hierarchy changes. All fields are optional
 * to support partial updates of layer attributes.
 */
final readonly class UpdateLayerRequestDTO
{
    public function __construct(
        /**
         * New display name for the layer
         * Used for layer identification in the layers panel and timeline
         * Must be 255 characters or less if provided
         */
        #[Assert\Length(
            max: 255, 
            maxMessage: 'Layer name cannot be longer than 255 characters'
        )]
        public ?string $name = null,

        /**
         * Layer-specific visual and behavior properties
         * Contains type-specific attributes like text styling, image filters, or shape appearance
         * Structure depends on layer type (text, image, shape, svg, etc.)
         * 
         * @var TextLayerProperties|ImageLayerProperties|ShapeLayerProperties|SvgLayerProperties|null
         */
        public TextLayerProperties|ImageLayerProperties|ShapeLayerProperties|SvgLayerProperties|null $properties = null,

        /**
         * 2D transformation matrix for layer positioning and scaling
         * Controls position, size, rotation, scale, skew, and opacity
         * Used by the canvas renderer for accurate layer placement
         */
        public ?Transform $transform = null,

        /**
         * Layer stacking order within its parent container
         * Higher values appear above lower values in the visual stack
         * Must be zero or positive integer
         */
        #[Assert\Type(type: 'integer', message: 'Z-index must be an integer')]
        #[Assert\PositiveOrZero(message: 'Z-index must be positive or zero')]
        public ?int $zIndex = null,

        /**
         * Layer visibility state in the design canvas
         * false: Layer is hidden from view but remains in the design
         * true: Layer is visible and rendered in the canvas
         */
        #[Assert\Type(type: 'boolean', message: 'Visible must be a boolean')]
        public ?bool $visible = null,

        /**
         * Layer edit protection state
         * true: Layer cannot be selected, moved, or modified
         * false: Layer can be freely edited and manipulated
         */
        #[Assert\Type(type: 'boolean', message: 'Locked must be a boolean')]
        public ?bool $locked = null,

        /**
         * Parent layer ID for hierarchical grouping
         * null: Layer is at root level of the design
         * string: Layer is nested under the specified parent layer
         * Used for group operations and layer organization
         */
        #[Assert\Type(type: 'string', message: 'Parent layer ID must be a string')]
        public ?string $parentLayerId = null,
    ) {}

    /**
     * Check if any layer data is provided for update
     * Used to validate that at least one field is being modified
     */
    public function hasAnyData(): bool
    {
        return $this->name !== null ||
               $this->properties !== null ||
               $this->transform !== null ||
               $this->zIndex !== null ||
               $this->visible !== null ||
               $this->locked !== null ||
               $this->parentLayerId !== null;
    }

    /**
     * Convert properties to array for legacy compatibility
     * Returns the properties as an array or null if no properties provided
     */
    public function getPropertiesArray(): ?array
    {
        return $this->properties?->toArray();
    }

    /**
     * Convert transform to array for legacy compatibility
     * Returns the transform as an array or null if no transform provided
     */
    public function getTransformArray(): ?array
    {
        return $this->transform?->toArray();
    }
}
