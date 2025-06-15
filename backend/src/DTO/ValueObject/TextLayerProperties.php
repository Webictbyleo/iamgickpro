<?php

declare(strict_types=1);

namespace App\DTO\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;

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
         * @var float $fontSize
         */
        #[Assert\Type(type: 'float', message: 'Font size must be an integer')]
        #[Assert\Range(
            min: 1,
            max: 500,
            notInRangeMessage: 'Font size must be between 1 and 500 pixels'
        )]
        public float $fontSize = 16,

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
