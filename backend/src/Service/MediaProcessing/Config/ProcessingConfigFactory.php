<?php

declare(strict_types=1);

namespace App\Service\MediaProcessing\Config;

/**
 * Factory for creating processing configurations
 */
class ProcessingConfigFactory
{
    /**
     * Create an image processing config
     */
    public static function createImage(
        ?int $width = null,
        ?int $height = null,
        ?int $quality = null,
        ?string $format = null,
        bool $maintainAspectRatio = true,
        bool $preserveTransparency = true,
        bool $stripMetadata = false,
        bool $progressive = false,
        ?string $backgroundColor = null,
        ?string $colorSpace = null,
        array $filters = [],
        array $customOptions = []
    ): ImageProcessingConfig {
        return new ImageProcessingConfig(
            $width,
            $height,
            $quality,
            $format,
            $maintainAspectRatio,
            $preserveTransparency,
            $stripMetadata,
            $progressive,
            $backgroundColor,
            $colorSpace,
            $filters,
            $customOptions
        );
    }

    /**
     * Create a video processing config
     */
    public static function createVideo(
        ?int $width = null,
        ?int $height = null,
        ?string $codec = null,
        ?int $bitrate = null,
        ?float $framerate = null,
        ?float $duration = null,
        ?float $startTime = null,
        ?string $format = null,
        bool $maintainAspectRatio = true,
        ?string $audioCodec = null,
        ?int $audioBitrate = null,
        ?int $audioSampleRate = null,
        array $filters = [],
        array $customOptions = []
    ): VideoProcessingConfig {
        return new VideoProcessingConfig(
            $width,
            $height,
            $codec,
            $bitrate,
            $framerate,
            $duration,
            $startTime,
            $format,
            $maintainAspectRatio,
            $audioCodec,
            $audioBitrate,
            $audioSampleRate,
            $filters,
            $customOptions
        );
    }

    /**
     * Create an audio processing config
     */
    public static function createAudio(
        ?string $codec = null,
        ?int $bitrate = null,
        ?int $sampleRate = null,
        ?int $channels = null,
        ?float $duration = null,
        ?float $startTime = null,
        ?string $format = null,
        ?float $volume = null,
        bool $normalize = false,
        array $filters = [],
        array $customOptions = []
    ): AudioProcessingConfig {
        return new AudioProcessingConfig(
            $codec,
            $bitrate,
            $sampleRate,
            $channels,
            $duration,
            $startTime,
            $format,
            $volume,
            $normalize,
            $filters,
            $customOptions
        );
    }
}
