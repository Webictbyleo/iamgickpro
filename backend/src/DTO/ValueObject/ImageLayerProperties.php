<?php

declare(strict_types=1);

namespace App\DTO\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;

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
        public string $src,

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
