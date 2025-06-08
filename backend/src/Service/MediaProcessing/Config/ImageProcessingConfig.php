<?php

declare(strict_types=1);

namespace App\Service\MediaProcessing\Config;

/**
 * Configuration for image processing operations
 */
readonly class ImageProcessingConfig implements ProcessingConfigInterface
{
    public function __construct(
        private ?int $width = null,
        private ?int $height = null,
        private ?int $quality = null,
        private ?string $format = null,
        private bool $maintainAspectRatio = true,
        private bool $preserveTransparency = true,
        private bool $stripMetadata = false,
        private bool $progressive = false,
        private ?string $backgroundColor = null,
        private ?string $colorSpace = null,
        private array $filters = [],
        private array $customOptions = []
    ) {}

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getQuality(): ?int
    {
        return $this->quality;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function shouldMaintainAspectRatio(): bool
    {
        return $this->maintainAspectRatio;
    }

    public function shouldPreserveTransparency(): bool
    {
        return $this->preserveTransparency;
    }

    public function shouldStripMetadata(): bool
    {
        return $this->stripMetadata;
    }

    public function shouldBeProgressive(): bool
    {
        return $this->progressive;
    }

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function getColorSpace(): ?string
    {
        return $this->colorSpace;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getCustomOptions(): array
    {
        return $this->customOptions;
    }

    public function hasResize(): bool
    {
        return $this->width !== null || $this->height !== null;
    }

    public function hasQuality(): bool
    {
        return $this->quality !== null;
    }

    public function hasFormat(): bool
    {
        return $this->format !== null;
    }

    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'quality' => $this->quality,
            'format' => $this->format,
            'maintainAspectRatio' => $this->maintainAspectRatio,
            'preserveTransparency' => $this->preserveTransparency,
            'stripMetadata' => $this->stripMetadata,
            'progressive' => $this->progressive,
            'backgroundColor' => $this->backgroundColor,
            'colorSpace' => $this->colorSpace,
            'filters' => $this->filters,
            'customOptions' => $this->customOptions,
        ];
    }

    public function getType(): string
    {
        return 'image';
    }

    public function validate(): bool
    {
        // Basic validation - ensure quality is within valid range if set
        if ($this->quality !== null && ($this->quality < 1 || $this->quality > 100)) {
            return false;
        }

        // Ensure dimensions are positive if set
        if ($this->width !== null && $this->width <= 0) {
            return false;
        }
        
        if ($this->height !== null && $this->height <= 0) {
            return false;
        }

        return true;
    }
}
