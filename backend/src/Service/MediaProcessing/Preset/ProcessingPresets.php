<?php

declare(strict_types=1);

namespace App\Service\MediaProcessing\Preset;

use App\Service\MediaProcessing\Config\{ImageProcessingConfig, VideoProcessingConfig, AudioProcessingConfig};

/**
 * Processing Presets Manager
 * 
 * Provides predefined processing configurations for common use cases
 * like web optimization, print quality, social media formats, etc.
 */
class ProcessingPresets
{
    /**
     * Image presets for common use cases
     */
    public static function getImagePresets(): array
    {
        return [
            'web_optimized' => new ImageProcessingConfig(
                width: 1920,
                height: 1080,
                quality: 85,
                format: 'webp',
                maintainAspectRatio: true,
                stripMetadata: true
            ),

            'thumbnail_small' => new ImageProcessingConfig(
                width: 150,
                height: 150,
                quality: 80,
                format: 'webp',
                maintainAspectRatio: true,
                stripMetadata: true
            ),

            'thumbnail_medium' => new ImageProcessingConfig(
                width: 300,
                height: 300,
                quality: 85,
                format: 'webp',
                maintainAspectRatio: true,
                stripMetadata: true
            ),

            'thumbnail_large' => new ImageProcessingConfig(
                width: 600,
                height: 600,
                quality: 85,
                format: 'webp',
                maintainAspectRatio: true,
                stripMetadata: true
            ),

            'print_quality' => new ImageProcessingConfig(
                width: 3000,
                height: 3000,
                quality: 95,
                format: 'png',
                maintainAspectRatio: true
            ),

            'social_media_square' => new ImageProcessingConfig(
                width: 1080,
                height: 1080,
                quality: 85,
                format: 'jpeg',
                maintainAspectRatio: false,
                stripMetadata: true
            ),

            'social_media_story' => new ImageProcessingConfig(
                width: 1080,
                height: 1920,
                quality: 85,
                format: 'jpeg',
                maintainAspectRatio: false,
                stripMetadata: true
            ),

            'email_friendly' => new ImageProcessingConfig(
                width: 800,
                height: 600,
                quality: 75,
                format: 'jpeg',
                maintainAspectRatio: true,
                stripMetadata: true
            ),

            'high_compression' => new ImageProcessingConfig(
                width: 1280,
                height: 720,
                quality: 65,
                format: 'webp',
                maintainAspectRatio: true,
                stripMetadata: true
            ),
        ];
    }

    /**
     * Video presets for common use cases
     */
    public static function getVideoPresets(): array
    {
        return [
            'web_hd' => new VideoProcessingConfig(
                width: 1920,
                height: 1080,
                codec: 'libx264',
                audioCodec: 'aac',
                bitrate: 5000000, // 5000k in bits
                framerate: 30.0,
                format: 'mp4'
            ),

            'web_sd' => new VideoProcessingConfig(
                width: 1280,
                height: 720,
                codec: 'libx264',
                audioCodec: 'aac',
                bitrate: 2500000, // 2500k in bits
                framerate: 30.0,
                format: 'mp4'
            ),

            'mobile_optimized' => new VideoProcessingConfig(
                width: 854,
                height: 480,
                codec: 'libx264',
                audioCodec: 'aac',
                bitrate: 1200000, // 1200k in bits
                framerate: 24.0,
                format: 'mp4'
            ),

            'social_media_square' => new VideoProcessingConfig(
                width: 1080,
                height: 1080,
                codec: 'libx264',
                audioCodec: 'aac',
                bitrate: 3000000, // 3000k in bits
                framerate: 30.0,
                format: 'mp4'
            ),

            'gif_conversion' => new VideoProcessingConfig(
                width: 480,
                height: 480,
                framerate: 12.0,
                duration: 10.0,
                format: 'gif'
            ),

            'webm_streaming' => new VideoProcessingConfig(
                width: 1920,
                height: 1080,
                codec: 'libvpx-vp9',
                audioCodec: 'libopus',
                bitrate: 4000000, // 4000k in bits
                framerate: 30.0,
                format: 'webm'
            ),

            'high_compression' => new VideoProcessingConfig(
                width: 1280,
                height: 720,
                codec: 'libx265',
                audioCodec: 'aac',
                bitrate: 1000000, // 1000k in bits
                framerate: 24.0,
                format: 'mp4'
            ),
        ];
    }

    /**
     * Audio presets for common use cases
     */
    public static function getAudioPresets(): array
    {
        return [
            'high_quality' => new AudioProcessingConfig(
                format: 'flac',
                bitrate: 1411000, // 1411k in bits
                sampleRate: 44100,
                channels: 2
            ),

            'web_streaming' => new AudioProcessingConfig(
                format: 'mp3',
                bitrate: 192000, // 192k in bits
                sampleRate: 44100,
                channels: 2
            ),

            'podcast' => new AudioProcessingConfig(
                format: 'mp3',
                bitrate: 128000, // 128k in bits
                sampleRate: 44100,
                channels: 2,
                normalize: true
            ),

            'mobile_optimized' => new AudioProcessingConfig(
                format: 'aac',
                bitrate: 128000, // 128k in bits
                sampleRate: 44100,
                channels: 2
            ),

            'voice_only' => new AudioProcessingConfig(
                format: 'mp3',
                bitrate: 64000, // 64k in bits
                sampleRate: 22050,
                channels: 1,
                normalize: true
            ),

            'lossless' => new AudioProcessingConfig(
                format: 'wav',
                bitrate: 1411000, // 1411k in bits
                sampleRate: 44100,
                channels: 2
            ),

            'compressed' => new AudioProcessingConfig(
                format: 'ogg',
                bitrate: 96000, // 96k in bits
                sampleRate: 44100,
                channels: 2
            ),
        ];
    }

    /**
     * Get preset by name and type
     */
    public static function getPreset(string $type, string $name): ?object
    {
        $presets = match ($type) {
            'image' => self::getImagePresets(),
            'video' => self::getVideoPresets(),
            'audio' => self::getAudioPresets(),
            default => []
        };

        return $presets[$name] ?? null;
    }

    /**
     * Get all preset names by type
     */
    public static function getPresetNames(string $type): array
    {
        return array_keys(match ($type) {
            'image' => self::getImagePresets(),
            'video' => self::getVideoPresets(),
            'audio' => self::getAudioPresets(),
            default => []
        });
    }

    /**
     * Create a custom preset based on an existing one
     */
    public static function createCustomPreset(string $type, string $baseName, array $overrides): ?object
    {
        $basePreset = self::getPreset($type, $baseName);
        if (!$basePreset) {
            return null;
        }

        return match ($type) {
            'image' => new ImageProcessingConfig(...array_merge(
                get_object_vars($basePreset),
                $overrides
            )),
            'video' => new VideoProcessingConfig(...array_merge(
                get_object_vars($basePreset),
                $overrides
            )),
            'audio' => new AudioProcessingConfig(...array_merge(
                get_object_vars($basePreset),
                $overrides
            )),
            default => null
        };
    }
}
