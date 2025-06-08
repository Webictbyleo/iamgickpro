<?php

declare(strict_types=1);

namespace App\Service\MediaProcessing\Config;

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
