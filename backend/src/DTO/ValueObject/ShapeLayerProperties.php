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
         * Type of shape to render (rectangle, circle, ellipse, triangle, polygon, star, line, arrow)
         * @var string $shapeType
         */
        #[Assert\Choice(
            choices: ['rectangle', 'circle', 'ellipse', 'triangle', 'polygon', 'star', 'line', 'arrow'],
            message: 'Invalid shape type'
        )]
        public string $shapeType = 'rectangle',

        /**
         * Fill configuration for the shape
         * Supports solid colors, gradients, and patterns in a unified structure:
         * 
         * Solid Color:
         * ['type' => 'solid', 'color' => '#hex', 'opacity' => 0.0-1.0]
         * 
         * Linear Gradient:
         * ['type' => 'linear', 'colors' => [['color' => '#hex', 'stop' => 0.0-1.0]], 'angle' => 0-360]
         * 
         * Radial Gradient:
         * ['type' => 'radial', 'colors' => [['color' => '#hex', 'stop' => 0.0-1.0]], 'centerX' => 0.0-1.0, 'centerY' => 0.0-1.0, 'radius' => 0.0-1.0]
         * 
         * Pattern:
         * ['type' => 'pattern', 'patternType' => 'dots|stripes|grid', 'size' => int, 'color' => '#hex', 'backgroundColor' => '#hex', 'spacing' => int, 'angle' => 0-360]
         * 
         * @var array $fill
         */
        #[Assert\Type(type: 'array', message: 'Fill must be an array')]
        #[Assert\NotBlank(message: 'Fill configuration is required')]
        public array $fill = ['type' => 'solid', 'color' => '#cccccc', 'opacity' => 1.0],

        /**
         * Border/stroke color of the shape as hex value
         * @var string $stroke
         */
        #[Assert\Regex(
            pattern: '/^(#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})|none)$/',
            message: 'Stroke color must be a valid hex color code or "none"'
        )]
        public string $stroke = 'none',

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
         * Stroke dash array for dashed lines (e.g., "5,5" for dashed, "10,5,2,5" for dash-dot)
         * @var ?string $strokeDashArray
         */
        #[Assert\Regex(
            pattern: '/^[\d\s,\.]+$/',
            message: 'Stroke dash array must contain only numbers, spaces, commas, and dots'
        )]
        public ?string $strokeDashArray = null,

        /**
         * How line ends are drawn (butt, round, square)
         * @var string $strokeLineCap
         */
        #[Assert\Choice(
            choices: ['butt', 'round', 'square'],
            message: 'Stroke line cap must be one of: butt, round, square'
        )]
        public string $strokeLineCap = 'butt',

        /**
         * How line joints are drawn (miter, round, bevel)
         * @var string $strokeLineJoin
         */
        #[Assert\Choice(
            choices: ['miter', 'round', 'bevel'],
            message: 'Stroke line join must be one of: miter, round, bevel'
        )]
        public string $strokeLineJoin = 'miter',

        /**
         * Corner radius in pixels for rounded corners (applies to rectangles)
         * @var float $cornerRadius
         */
        #[Assert\Type(type: 'float', message: 'Corner radius must be a number')]
        #[Assert\PositiveOrZero(message: 'Corner radius must be positive or zero')]
        public float $cornerRadius = 0.0,

        /**
         * Number of sides for polygon shapes (3-20)
         * @var int $sides
         */
        #[Assert\Type(type: 'integer', message: 'Number of sides must be an integer')]
        #[Assert\Range(
            min: 3,
            max: 20,
            notInRangeMessage: 'Number of sides must be between 3 and 20'
        )]
        public int $sides = 6,

        /**
         * Number of points for star shapes (3-20)
         * @var int $points
         */
        #[Assert\Type(type: 'integer', message: 'Number of points must be an integer')]
        #[Assert\Range(
            min: 3,
            max: 20,
            notInRangeMessage: 'Number of points must be between 3 and 20'
        )]
        public int $points = 5,

        /**
         * Inner radius ratio for star shapes (0.1-0.9)
         * @var float $innerRadius
         */
        #[Assert\Type(type: 'float', message: 'Inner radius must be a number')]
        #[Assert\Range(
            min: 0.1,
            max: 0.9,
            notInRangeMessage: 'Inner radius must be between 0.1 and 0.9'
        )]
        public float $innerRadius = 0.4,

        /**
         * Starting X coordinate for line shapes
         * @var float $x1
         */
        #[Assert\Type(type: 'float', message: 'X1 coordinate must be a number')]
        public float $x1 = 0.0,

        /**
         * Starting Y coordinate for line shapes
         * @var float $y1
         */
        #[Assert\Type(type: 'float', message: 'Y1 coordinate must be a number')]
        public float $y1 = 0.0,

        /**
         * Ending X coordinate for line shapes
         * @var float $x2
         */
        #[Assert\Type(type: 'float', message: 'X2 coordinate must be a number')]
        public float $x2 = 100.0,

        /**
         * Ending Y coordinate for line shapes
         * @var float $y2
         */
        #[Assert\Type(type: 'float', message: 'Y2 coordinate must be a number')]
        public float $y2 = 0.0,

        /**
         * Shadow effect configuration
         * Structure: ['enabled' => bool, 'offsetX' => float, 'offsetY' => float, 'blur' => float, 'color' => '#hex', 'opacity' => float]
         * @var ?array $shadow
         */
        #[Assert\Type(type: 'array', message: 'Shadow must be an array')]
        public ?array $shadow = null,

        /**
         * Glow effect configuration
         * Structure: ['enabled' => bool, 'blur' => float, 'color' => '#hex', 'opacity' => float]
         * @var ?array $glow
         */
        #[Assert\Type(type: 'array', message: 'Glow must be an array')]
        public ?array $glow = null,
    ) {}

    public function toArray(): array
    {
        return [
            'shapeType' => $this->shapeType,
            'fill' => $this->fill,
            'stroke' => $this->stroke,
            'strokeWidth' => $this->strokeWidth,
            'strokeOpacity' => $this->strokeOpacity,
            'strokeDashArray' => $this->strokeDashArray,
            'strokeLineCap' => $this->strokeLineCap,
            'strokeLineJoin' => $this->strokeLineJoin,
            'cornerRadius' => $this->cornerRadius,
            'sides' => $this->sides,
            'points' => $this->points,
            'innerRadius' => $this->innerRadius,
            'x1' => $this->x1,
            'y1' => $this->y1,
            'x2' => $this->x2,
            'y2' => $this->y2,
            'shadow' => $this->shadow,
            'glow' => $this->glow,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            shapeType: $data['shapeType'] ?? 'rectangle',
            fill: $data['fill'] ?? ['type' => 'solid', 'color' => '#cccccc', 'opacity' => 1.0],
            stroke: $data['stroke'] ?? 'none',
            strokeWidth: (float)($data['strokeWidth'] ?? 0.0),
            strokeOpacity: (float)($data['strokeOpacity'] ?? 1.0),
            strokeDashArray: $data['strokeDashArray'] ?? null,
            strokeLineCap: $data['strokeLineCap'] ?? 'butt',
            strokeLineJoin: $data['strokeLineJoin'] ?? 'miter',
            cornerRadius: (float)($data['cornerRadius'] ?? 0.0),
            sides: (int)($data['sides'] ?? 6),
            points: (int)($data['points'] ?? 5),
            innerRadius: (float)($data['innerRadius'] ?? 0.4),
            x1: (float)($data['x1'] ?? 0.0),
            y1: (float)($data['y1'] ?? 0.0),
            x2: (float)($data['x2'] ?? 100.0),
            y2: (float)($data['y2'] ?? 0.0),
            shadow: $data['shadow'] ?? null,
            glow: $data['glow'] ?? null,
        );
    }
}
