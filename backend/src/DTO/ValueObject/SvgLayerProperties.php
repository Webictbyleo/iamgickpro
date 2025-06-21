<?php

declare(strict_types=1);

namespace App\DTO\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * SVG Layer Properties DTO for handling SVG vector graphics
 * Matches frontend SVGLayerProperties interface
 */
final readonly class SvgLayerProperties extends LayerProperties
{
    public function __construct(
        #[Assert\NotBlank(message: "SVG source URL is required")]
        public string $src,

        #[Assert\Length(max: 500, maxMessage: "ViewBox cannot exceed 500 characters")]
        public ?string $viewBox = null,

        #[Assert\Length(max: 100, maxMessage: "PreserveAspectRatio cannot exceed 100 characters")]
        public ?string $preserveAspectRatio = null,

        /**
         * Map of element IDs/classes to fill colors for customization
         * @var array<string, string>|null
         */
        public ?array $fillColors = null,

        /**
         * Map of element IDs/classes to stroke colors
         * @var array<string, string>|null
         */
        public ?array $strokeColors = null,

        /**
         * Map of element IDs/classes to stroke widths
         * @var array<string, float>|null
         */
        public ?array $strokeWidths = null,

        #[Assert\Positive(message: "Original width must be positive")]
        public ?float $originalWidth = null,

        #[Assert\Positive(message: "Original height must be positive")]
        public ?float $originalHeight = null,
    ) {}

    public function toArray(): array
    {
        return [
            'src' => $this->src,
            'viewBox' => $this->viewBox,
            'preserveAspectRatio' => $this->preserveAspectRatio,
            'fillColors' => $this->fillColors,
            'strokeColors' => $this->strokeColors,
            'strokeWidths' => $this->strokeWidths,
            'originalWidth' => $this->originalWidth,
            'originalHeight' => $this->originalHeight,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            src: $data['src'] ?? '',
            viewBox: $data['viewBox'] ?? null,
            preserveAspectRatio: $data['preserveAspectRatio'] ?? null,
            fillColors: $data['fillColors'] ?? null,
            strokeColors: $data['strokeColors'] ?? null,
            strokeWidths: isset($data['strokeWidths']) ? array_map('floatval', $data['strokeWidths']) : null,
            originalWidth: isset($data['originalWidth']) ? (float) $data['originalWidth'] : null,
            originalHeight: isset($data['originalHeight']) ? (float) $data['originalHeight'] : null,
        );
    }
}
