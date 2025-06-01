<?php

declare(strict_types=1);

namespace App\DTO\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a 2D transformation matrix for layer positioning and scaling
 */
final readonly class Transform
{
    public function __construct(
        /**
         * X coordinate position of the layer in pixels
         * @var float $x
         */
        #[Assert\Type(type: 'float', message: 'X position must be a number')]
        public float $x = 0.0,

        /**
         * Y coordinate position of the layer in pixels
         * @var float $y
         */
        #[Assert\Type(type: 'float', message: 'Y position must be a number')]
        public float $y = 0.0,

        /**
         * Width of the layer in pixels
         * @var float $width
         */
        #[Assert\Type(type: 'float', message: 'Width must be a number')]
        #[Assert\Positive(message: 'Width must be positive')]
        public float $width = 100.0,

        /**
         * Height of the layer in pixels
         * @var float $height
         */
        #[Assert\Type(type: 'float', message: 'Height must be a number')]
        #[Assert\Positive(message: 'Height must be positive')]
        public float $height = 100.0,

        /**
         * Rotation angle in degrees (0-360). Positive values rotate clockwise
         * @var float $rotation
         */
        #[Assert\Type(type: 'float', message: 'Rotation must be a number')]
        #[Assert\Range(
            min: -360,
            max: 360,
            notInRangeMessage: 'Rotation must be between -360 and 360 degrees'
        )]
        public float $rotation = 0.0,

        /**
         * Horizontal scale factor (1.0 = normal size, 2.0 = double width)
         * @var float $scaleX
         */
        #[Assert\Type(type: 'float', message: 'Scale X must be a number')]
        #[Assert\Positive(message: 'Scale X must be positive')]
        public float $scaleX = 1.0,

        /**
         * Vertical scale factor (1.0 = normal size, 2.0 = double height)
         * @var float $scaleY
         */
        #[Assert\Type(type: 'float', message: 'Scale Y must be a number')]
        #[Assert\Positive(message: 'Scale Y must be positive')]
        public float $scaleY = 1.0,

        /**
         * Horizontal skew transformation in degrees
         * @var float $skewX
         */
        #[Assert\Type(type: 'float', message: 'Skew X must be a number')]
        public float $skewX = 0.0,

        /**
         * Vertical skew transformation in degrees
         * @var float $skewY
         */
        #[Assert\Type(type: 'float', message: 'Skew Y must be a number')]
        public float $skewY = 0.0,

        /**
         * Layer opacity from 0.0 (transparent) to 1.0 (opaque)
         * @var float $opacity
         */
        #[Assert\Type(type: 'float', message: 'Opacity must be a number')]
        #[Assert\Range(
            min: 0.0,
            max: 1.0,
            notInRangeMessage: 'Opacity must be between 0 and 1'
        )]
        public float $opacity = 1.0
    ) {}

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height,
            'rotation' => $this->rotation,
            'scaleX' => $this->scaleX,
            'scaleY' => $this->scaleY,
            'skewX' => $this->skewX,
            'skewY' => $this->skewY,
            'opacity' => $this->opacity,
        ];
    }

    /**
     * Create Transform from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            x: (float)($data['x'] ?? 0.0),
            y: (float)($data['y'] ?? 0.0),
            width: (float)($data['width'] ?? 100.0),
            height: (float)($data['height'] ?? 100.0),
            rotation: (float)($data['rotation'] ?? 0.0),
            scaleX: (float)($data['scaleX'] ?? 1.0),
            scaleY: (float)($data['scaleY'] ?? 1.0),
            skewX: (float)($data['skewX'] ?? 0.0),
            skewY: (float)($data['skewY'] ?? 0.0),
            opacity: (float)($data['opacity'] ?? 1.0),
        );
    }
}
