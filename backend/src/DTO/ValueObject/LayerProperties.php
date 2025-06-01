<?php

declare(strict_types=1);

namespace App\DTO\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Base class for layer properties - different layer types extend this
 */
abstract readonly class LayerProperties
{
    /**
     * Convert properties to array for storage
     */
    abstract public function toArray(): array;

    /**
     * Create properties from array data
     */
    abstract public static function fromArray(array $data): static;
}

/**
 * Properties specific to text layers
 */
final readonly class TextLayerProperties extends LayerProperties
{
    public function __construct(
        /**
         * The text content to display in the layer
         * @var string $text
         */
        #[Assert\NotBlank(message: 'Text content is required')]
        #[Assert\Length(max: 10000, maxMessage: 'Text content cannot exceed 10000 characters')]
        public string $text = '',

        /**
         * Font family name (e.g., Arial, Helvetica, Times New Roman)
         * @var string $fontFamily
         */
        #[Assert\NotBlank(message: 'Font family is required')]
        #[Assert\Length(max: 255, maxMessage: 'Font family name cannot exceed 255 characters')]
        public string $fontFamily = 'Arial',

        /**
         * Font size in pixels (1-500px)
         * @var int $fontSize
         */
        #[Assert\Type(type: 'integer', message: 'Font size must be an integer')]
        #[Assert\Range(
            min: 1,
            max: 500,
            notInRangeMessage: 'Font size must be between 1 and 500 pixels'
        )]
        public int $fontSize = 16,

        /**
         * Font weight (normal, bold, or numeric values 100-900)
         * @var string $fontWeight
         */
        #[Assert\Choice(
            choices: ['normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900'],
            message: 'Invalid font weight'
        )]
        public string $fontWeight = 'normal',

        /**
         * Font style (normal, italic, or oblique)
         * @var string $fontStyle
         */
        #[Assert\Choice(
            choices: ['normal', 'italic', 'oblique'],
            message: 'Invalid font style'
        )]
        public string $fontStyle = 'normal',

        /**
         * Text alignment within the layer bounds
         * @var string $textAlign
         */
        #[Assert\Choice(
            choices: ['left', 'center', 'right', 'justify'],
            message: 'Invalid text alignment'
        )]
        public string $textAlign = 'left',

        /**
         * Text color as hex value (e.g., #000000, #fff)
         * @var string $color
         */
        #[Assert\Regex(
            pattern: '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            message: 'Color must be a valid hex color code'
        )]
        public string $color = '#000000',

        /**
         * Line height multiplier for text spacing (1.0 = normal, 1.5 = 1.5x spacing)
         * @var float $lineHeight
         */
        #[Assert\Type(type: 'float', message: 'Line height must be a number')]
        #[Assert\Range(
            min: 0.1,
            max: 10.0,
            notInRangeMessage: 'Line height must be between 0.1 and 10.0'
        )]
        public float $lineHeight = 1.2,

        /**
         * Letter spacing in pixels (positive or negative values allowed)
         * @var float $letterSpacing
         */
        #[Assert\Type(type: 'float', message: 'Letter spacing must be a number')]
        public float $letterSpacing = 0.0,

        /**
         * Text decoration style (none, underline, overline, line-through)
         * @var string $textDecoration
         */
        #[Assert\Choice(
            choices: ['none', 'underline', 'overline', 'line-through'],
            message: 'Invalid text decoration'
        )]
        public string $textDecoration = 'none'
    ) {}

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'fontFamily' => $this->fontFamily,
            'fontSize' => $this->fontSize,
            'fontWeight' => $this->fontWeight,
            'fontStyle' => $this->fontStyle,
            'textAlign' => $this->textAlign,
            'color' => $this->color,
            'lineHeight' => $this->lineHeight,
            'letterSpacing' => $this->letterSpacing,
            'textDecoration' => $this->textDecoration,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            text: $data['text'] ?? '',
            fontFamily: $data['fontFamily'] ?? 'Arial',
            fontSize: (int)($data['fontSize'] ?? 16),
            fontWeight: $data['fontWeight'] ?? 'normal',
            fontStyle: $data['fontStyle'] ?? 'normal',
            textAlign: $data['textAlign'] ?? 'left',
            color: $data['color'] ?? '#000000',
            lineHeight: (float)($data['lineHeight'] ?? 1.2),
            letterSpacing: (float)($data['letterSpacing'] ?? 0.0),
            textDecoration: $data['textDecoration'] ?? 'none',
        );
    }
}

/**
 * Properties specific to image layers
 */
final readonly class ImageLayerProperties extends LayerProperties
{
    public function __construct(
        /**
         * Source URL or path to the image file
         * @var string $src
         */
        #[Assert\NotBlank(message: 'Image source URL is required')]
        #[Assert\Url(message: 'Image source must be a valid URL')]
        public string $src = '',

        /**
         * Alternative text for accessibility and fallback display
         * @var string $alt
         */
        #[Assert\Length(max: 255, maxMessage: 'Alt text cannot exceed 255 characters')]
        public string $alt = '',

        /**
         * How the image should be resized to fit its container (fill, contain, cover, none, scale-down)
         * @var string $objectFit
         */
        #[Assert\Choice(
            choices: ['fill', 'contain', 'cover', 'none', 'scale-down'],
            message: 'Invalid object fit value'
        )]
        public string $objectFit = 'contain',

        /**
         * Position of the image within its container when using object-fit
         * @var string $objectPosition
         */
        #[Assert\Choice(
            choices: ['center', 'top', 'bottom', 'left', 'right', 'top left', 'top right', 'bottom left', 'bottom right'],
            message: 'Invalid object position'
        )]
        public string $objectPosition = 'center',

        /**
         * Image quality percentage for compression (1-100, higher = better quality)
         * @var int $quality
         */
        #[Assert\Type(type: 'integer', message: 'Quality must be an integer')]
        #[Assert\Range(
            min: 1,
            max: 100,
            notInRangeMessage: 'Quality must be between 1 and 100'
        )]
        public int $quality = 100,

        /**
         * Image brightness multiplier (0.0 = black, 1.0 = normal, 2.0 = very bright)
         * @var float $brightness
         */
        #[Assert\Type(type: 'float', message: 'Brightness must be a number')]
        #[Assert\Range(
            min: 0.0,
            max: 2.0,
            notInRangeMessage: 'Brightness must be between 0 and 2'
        )]
        public float $brightness = 1.0,

        /**
         * Image contrast multiplier (0.0 = gray, 1.0 = normal, 2.0 = high contrast)
         * @var float $contrast
         */
        #[Assert\Type(type: 'float', message: 'Contrast must be a number')]
        #[Assert\Range(
            min: 0.0,
            max: 2.0,
            notInRangeMessage: 'Contrast must be between 0 and 2'
        )]
        public float $contrast = 1.0,

        /**
         * Image saturation multiplier (0.0 = grayscale, 1.0 = normal, 2.0 = vivid)
         * @var float $saturation
         */
        #[Assert\Type(type: 'float', message: 'Saturation must be a number')]
        #[Assert\Range(
            min: 0.0,
            max: 2.0,
            notInRangeMessage: 'Saturation must be between 0 and 2'
        )]
        public float $saturation = 1.0,

        /**
         * Blur radius in pixels (0.0 = sharp, higher values = more blur)
         * @var float $blur
         */
        #[Assert\Type(type: 'float', message: 'Blur must be a number')]
        #[Assert\PositiveOrZero(message: 'Blur must be positive or zero')]
        public float $blur = 0.0
    ) {}

    public function toArray(): array
    {
        return [
            'src' => $this->src,
            'alt' => $this->alt,
            'objectFit' => $this->objectFit,
            'objectPosition' => $this->objectPosition,
            'quality' => $this->quality,
            'brightness' => $this->brightness,
            'contrast' => $this->contrast,
            'saturation' => $this->saturation,
            'blur' => $this->blur,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            src: $data['src'] ?? '',
            alt: $data['alt'] ?? '',
            objectFit: $data['objectFit'] ?? 'contain',
            objectPosition: $data['objectPosition'] ?? 'center',
            quality: (int)($data['quality'] ?? 100),
            brightness: (float)($data['brightness'] ?? 1.0),
            contrast: (float)($data['contrast'] ?? 1.0),
            saturation: (float)($data['saturation'] ?? 1.0),
            blur: (float)($data['blur'] ?? 0.0),
        );
    }
}

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
