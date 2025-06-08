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

/**
 * Configuration for video processing operations
 */
readonly class VideoProcessingConfig implements ProcessingConfigInterface
{
    public function __construct(
        private ?int $width = null,
        private ?int $height = null,
        private ?string $codec = null,
        private ?int $bitrate = null,
        private ?float $framerate = null,
        private ?float $duration = null,
        private ?float $startTime = null,
        private ?string $format = null,
        private bool $maintainAspectRatio = true,
        private ?string $audioCodec = null,
        private ?int $audioBitrate = null,
        private ?int $audioSampleRate = null,
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

    public function getCodec(): ?string
    {
        return $this->codec;
    }

    public function getBitrate(): ?int
    {
        return $this->bitrate;
    }

    public function getFramerate(): ?float
    {
        return $this->framerate;
    }

    public function getDuration(): ?float
    {
        return $this->duration;
    }

    public function getStartTime(): ?float
    {
        return $this->startTime;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function shouldMaintainAspectRatio(): bool
    {
        return $this->maintainAspectRatio;
    }

    public function getAudioCodec(): ?string
    {
        return $this->audioCodec;
    }

    public function getAudioBitrate(): ?int
    {
        return $this->audioBitrate;
    }

    public function getAudioSampleRate(): ?int
    {
        return $this->audioSampleRate;
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

    public function hasTrimming(): bool
    {
        return $this->duration !== null || $this->startTime !== null;
    }

    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'codec' => $this->codec,
            'bitrate' => $this->bitrate,
            'framerate' => $this->framerate,
            'duration' => $this->duration,
            'startTime' => $this->startTime,
            'format' => $this->format,
            'maintainAspectRatio' => $this->maintainAspectRatio,
            'audioCodec' => $this->audioCodec,
            'audioBitrate' => $this->audioBitrate,
            'audioSampleRate' => $this->audioSampleRate,
            'filters' => $this->filters,
            'customOptions' => $this->customOptions,
        ];
    }

    public function getType(): string
    {
        return 'video';
    }

    public function validate(): bool
    {
        // Basic validation for video config
        if ($this->width !== null && $this->width <= 0) {
            return false;
        }
        
        if ($this->height !== null && $this->height <= 0) {
            return false;
        }

        if ($this->bitrate !== null && $this->bitrate <= 0) {
            return false;
        }

        if ($this->framerate !== null && $this->framerate <= 0) {
            return false;
        }

        return true;
    }
}

/**
 * Configuration for audio processing operations
 */
readonly class AudioProcessingConfig implements ProcessingConfigInterface
{
    public function __construct(
        private ?string $codec = null,
        private ?int $bitrate = null,
        private ?int $sampleRate = null,
        private ?int $channels = null,
        private ?float $duration = null,
        private ?float $startTime = null,
        private ?string $format = null,
        private ?float $volume = null,
        private bool $normalize = false,
        private array $filters = [],
        private array $customOptions = []
    ) {}

    public function getCodec(): ?string
    {
        return $this->codec;
    }

    public function getBitrate(): ?int
    {
        return $this->bitrate;
    }

    public function getSampleRate(): ?int
    {
        return $this->sampleRate;
    }

    public function getChannels(): ?int
    {
        return $this->channels;
    }

    public function getDuration(): ?float
    {
        return $this->duration;
    }

    public function getStartTime(): ?float
    {
        return $this->startTime;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function getVolume(): ?float
    {
        return $this->volume;
    }

    public function shouldNormalize(): bool
    {
        return $this->normalize;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getCustomOptions(): array
    {
        return $this->customOptions;
    }

    public function hasTrimming(): bool
    {
        return $this->duration !== null || $this->startTime !== null;
    }

    public function toArray(): array
    {
        return [
            'codec' => $this->codec,
            'bitrate' => $this->bitrate,
            'sampleRate' => $this->sampleRate,
            'channels' => $this->channels,
            'duration' => $this->duration,
            'startTime' => $this->startTime,
            'format' => $this->format,
            'volume' => $this->volume,
            'normalize' => $this->normalize,
            'filters' => $this->filters,
            'customOptions' => $this->customOptions,
        ];
    }

    public function getType(): string
    {
        return 'audio';
    }

    public function validate(): bool
    {
        // Basic validation for audio config
        if ($this->bitrate !== null && $this->bitrate <= 0) {
            return false;
        }
        
        if ($this->sampleRate !== null && $this->sampleRate <= 0) {
            return false;
        }

        if ($this->channels !== null && $this->channels <= 0) {
            return false;
        }

        if ($this->volume !== null && ($this->volume < 0 || $this->volume > 2)) {
            return false;
        }

        return true;
    }
}
