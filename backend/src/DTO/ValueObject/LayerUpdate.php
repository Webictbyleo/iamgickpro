<?php

declare(strict_types=1);

namespace App\DTO\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a single layer update in a bulk operation
 */
final readonly class LayerUpdate
{
    public function __construct(
        /**
         * Unique identifier of the layer to update
         * @var int $id
         */
        #[Assert\NotBlank(message: 'Layer ID is required')]
        #[Assert\Type(type: 'integer', message: 'Layer ID must be an integer')]
        #[Assert\Positive(message: 'Layer ID must be positive')]
        public int $id,

        /**
         * Display name of the layer for organization and reference
         * @var string|null $name
         */
        #[Assert\Length(max: 255, maxMessage: 'Layer name cannot be longer than 255 characters')]
        public ?string $name = null,

        /**
         * 2D transformation properties for positioning, scaling, and rotation
         * @var Transform|null $transform
         */
        public ?Transform $transform = null,

        /**
         * Layer-specific properties based on layer type.
         * 
         * @var TextLayerProperties|ImageLayerProperties|ShapeLayerProperties|null $properties
         */
        public ?LayerProperties $properties = null,

        /**
         * Layer stacking order (higher values appear on top)
         * @var int|null $zIndex
         */
        #[Assert\Type(type: 'integer', message: 'Z-index must be an integer')]
        #[Assert\PositiveOrZero(message: 'Z-index must be positive or zero')]
        public ?int $zIndex = null,

        /**
         * Whether the layer is visible on the canvas
         * @var bool|null $visible
         */
        #[Assert\Type(type: 'boolean', message: 'Visible must be a boolean')]
        public ?bool $visible = null,

        /**
         * Whether the layer is locked from editing and interaction
         * @var bool|null $locked
         */
        #[Assert\Type(type: 'boolean', message: 'Locked must be a boolean')]
        public ?bool $locked = null,

        /**
         * ID of the parent layer for grouping (null for root-level layers)
         * @var string|null $parentLayerId
         */
        #[Assert\Type(type: 'string', message: 'Parent layer ID must be a string')]
        public ?string $parentLayerId = null
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'transform' => $this->transform?->toArray(),
            'properties' => $this->properties?->toArray(),
            'zIndex' => $this->zIndex,
            'visible' => $this->visible,
            'locked' => $this->locked,
            'parentLayerId' => $this->parentLayerId,
        ], fn($value) => $value !== null);
    }

    public static function fromArray(array $data): self
    {
        $transform = null;
        if (isset($data['transform']) && is_array($data['transform'])) {
            $transform = Transform::fromArray($data['transform']);
        }

        $properties = null;
        if (isset($data['properties']) && is_array($data['properties'])) {
            // For now, we'll store as generic properties until we have layer type
            // In a real implementation, you'd determine the type and create appropriate properties
            $properties = null; // This would need to be resolved based on layer type
        }

        return new self(
            id: (int)$data['id'],
            name: $data['name'] ?? null,
            transform: $transform,
            properties: $properties,
            zIndex: isset($data['zIndex']) ? (int)$data['zIndex'] : null,
            visible: isset($data['visible']) ? (bool)$data['visible'] : null,
            locked: isset($data['locked']) ? (bool)$data['locked'] : null,
            parentLayerId: $data['parentLayerId'] ?? null,
        );
    }

    /**
     * Check if this update contains any actual changes
     */
    public function hasChanges(): bool
    {
        return $this->name !== null ||
               $this->transform !== null ||
               $this->properties !== null ||
               $this->zIndex !== null ||
               $this->visible !== null ||
               $this->locked !== null ||
               $this->parentLayerId !== null;
    }
}
