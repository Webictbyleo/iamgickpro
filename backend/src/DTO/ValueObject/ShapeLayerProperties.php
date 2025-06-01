<?php

declare(strict_types=1);

namespace App\DTO\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Properties specific to shape layers
 */
final readonly class ShapeLayerProperties extends LayerProperties
{
    public function __construct(
        /**
         * Type of shape to render (rectangle, circle, ellipse, triangle, polygon, star, line)
         * @var string $shapeType
         */
        #[Assert\Choice(
            choices: ['rectangle', 'circle', 'ellipse', 'triangle', 'polygon', 'star', 'line'],
            message: 'Invalid shape type'
        )]
        public string $shapeType = 'rectangle',

        /**
         * Fill color of the shape as hex value (e.g., #000000, #fff)
         * @var string $fillColor
         */
        #[Assert\Regex(
            pattern: '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            message: 'Fill color must be a valid hex color code'
        )]
        public string $fillColor = '#000000',

        /**
         * Opacity of the fill color (0.0 = transparent, 1.0 = opaque)
         * @var float $fillOpacity
         */
        #[Assert\Type(type: 'float', message: 'Fill opacity must be a number')]
        #[Assert\Range(
            min: 0.0,
            max: 1.0,
            notInRangeMessage: 'Fill opacity must be between 0 and 1'
        )]
        public float $fillOpacity = 1.0,

        /**
         * Border/stroke color of the shape as hex value
         * @var string $strokeColor
         */
        #[Assert\Regex(
            pattern: '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            message: 'Stroke color must be a valid hex color code'
        )]
        public string $strokeColor = '#000000',

        /**
         * Width of the border/stroke in pixels (0 = no border)
         * @var float $strokeWidth
         */
        #[Assert\Type(type: 'float', message: 'Stroke width must be a number')]
        #[Assert\PositiveOrZero(message: 'Stroke width must be positive or zero')]
        public float $strokeWidth = 0.0,

        /**
         * Opacity of the border/stroke (0.0 = transparent, 1.0 = opaque)
         * @var float $strokeOpacity
         */
        #[Assert\Type(type: 'float', message: 'Stroke opacity must be a number')]
        #[Assert\Range(
            min: 0.0,
            max: 1.0,
            notInRangeMessage: 'Stroke opacity must be between 0 and 1'
        )]
        public float $strokeOpacity = 1.0,

        /**
         * Border radius in pixels for rounded corners (applies to rectangles)
         * @var float $borderRadius
         */
        #[Assert\Type(type: 'float', message: 'Border radius must be a number')]
        #[Assert\PositiveOrZero(message: 'Border radius must be positive or zero')]
        public float $borderRadius = 0.0,

        /**
         * Number of sides for polygon and star shapes (3-20)
         * @var int $sides
         */
        #[Assert\Type(type: 'integer', message: 'Number of sides must be an integer')]
        #[Assert\Range(
            min: 3,
            max: 20,
            notInRangeMessage: 'Number of sides must be between 3 and 20'
        )]
        public int $sides = 3
    ) {}

    public function toArray(): array
    {
        return [
            'shapeType' => $this->shapeType,
            'fillColor' => $this->fillColor,
            'fillOpacity' => $this->fillOpacity,
            'strokeColor' => $this->strokeColor,
            'strokeWidth' => $this->strokeWidth,
            'strokeOpacity' => $this->strokeOpacity,
            'borderRadius' => $this->borderRadius,
            'sides' => $this->sides,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            shapeType: $data['shapeType'] ?? 'rectangle',
            fillColor: $data['fillColor'] ?? '#000000',
            fillOpacity: (float)($data['fillOpacity'] ?? 1.0),
            strokeColor: $data['strokeColor'] ?? '#000000',
            strokeWidth: (float)($data['strokeWidth'] ?? 0.0),
            strokeOpacity: (float)($data['strokeOpacity'] ?? 1.0),
            borderRadius: (float)($data['borderRadius'] ?? 0.0),
            sides: (int)($data['sides'] ?? 3),
        );
    }
}
