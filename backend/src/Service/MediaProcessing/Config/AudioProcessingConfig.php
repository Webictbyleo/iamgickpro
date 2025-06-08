<?php

declare(strict_types=1);

namespace App\Service\MediaProcessing\Config;

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
