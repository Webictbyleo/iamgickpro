<?php

declare(strict_types=1);

namespace App\Service\MediaProcessing;

use App\Service\MediaProcessing\Config\{ImageProcessingConfig, VideoProcessingConfig, AudioProcessingConfig, ProcessingConfigInterface, ProcessingConfig};
use App\Service\MediaProcessing\Processor\{ImageMagickProcessor, FfmpegProcessor};
use App\Service\MediaProcessing\Result\ProcessingResult;
use App\Service\Svg\SvgRendererService;
use Symfony\Component\HttpFoundation\File\File;
use Psr\Log\LoggerInterface;

/**
 * Unified Media Processing Service
 * 
 * Central service for processing all types of media files including images, videos, and audio.
 * Coordinates between ImageMagick, FFmpeg processors and provides intelligent format detection
 * and conversion capabilities.
 */
class MediaProcessingService
{
    // Supported image types for ImageMagick
    private const IMAGE_TYPES = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
        'image/bmp', 'image/tiff', 'image/svg+xml', 'image/x-icon'
    ];

    // Supported video types for FFmpeg
    private const VIDEO_TYPES = [
        'video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/flv',
        'video/webm', 'video/mkv', 'video/m4v', 'video/3gp', 'video/quicktime'
    ];

    // Supported audio types for FFmpeg
    private const AUDIO_TYPES = [
        'audio/mp3', 'audio/wav', 'audio/flac', 'audio/aac', 'audio/ogg',
        'audio/m4a', 'audio/wma', 'audio/aiff', 'audio/mpeg'
    ];

    public function __construct(
        private readonly ImageMagickProcessor $imageMagickProcessor,
        private readonly FfmpegProcessor $ffmpegProcessor,
        private readonly AsyncMediaProcessingService $asyncService,
        private readonly SvgRendererService $svgRendererService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Process media file with automatic type detection
     */
    public function processMedia(
        string $inputPath,
        string $outputPath,
        ProcessingConfigInterface $config,
        bool $async = false
    ): ProcessingResult {
        try {
            // Validate input file exists
            if (!file_exists($inputPath)) {
                return ProcessingResult::failure(
                    "Input file does not exist: {$inputPath}"
                );
            }

            // If async processing requested, queue the job
            if ($async) {
                return $this->asyncService->queueProcessing($inputPath, $outputPath, $config);
            }

            // Detect media type and process accordingly
            $mimeType = $this->detectMimeType($inputPath);
            
            return match (true) {
                $this->isImageType($mimeType) => $this->processImage($inputPath, $outputPath, $config),
                $this->isVideoType($mimeType) => $this->processVideo($inputPath, $outputPath, $config),
                $this->isAudioType($mimeType) => $this->processAudio($inputPath, $outputPath, $config),
                default => ProcessingResult::failure(
                    "Unsupported media type: {$mimeType}",
                    ['mime_type' => $mimeType]
                )
            };

        } catch (\Exception $e) {
            $this->logger->error('Media processing failed', [
                'input_path' => $inputPath,
                'output_path' => $outputPath,
                'error' => $e->getMessage()
            ]);

            return ProcessingResult::failure(
                'Processing failed: ' . $e->getMessage(),
                ['exception' => $e::class]
            );
        }
    }

    /**
     * Process image files using ImageMagick
     */
    public function processImage(
        string $inputPath,
        string $outputPath,
        ProcessingConfigInterface $config
    ): ProcessingResult {
        // Ensure we have an image processing config
        if (!$config instanceof ImageProcessingConfig) {
            $config = new ImageProcessingConfig();
        }

        // Handle SVG files with special processing
        if (str_ends_with(strtolower($inputPath), '.svg')) {
            return $this->processSvg($inputPath, $outputPath, $config);
        }

        return $this->imageMagickProcessor->processImage($inputPath, $outputPath, $config);
    }

    /**
     * Process video files using FFmpeg
     */
    public function processVideo(
        string $inputPath,
        string $outputPath,
        ProcessingConfigInterface $config
    ): ProcessingResult {
        // Ensure we have a video processing config
        if (!$config instanceof VideoProcessingConfig) {
            $config = new VideoProcessingConfig();
        }

        return $this->ffmpegProcessor->processVideo($inputPath, $outputPath, $config);
    }

    /**
     * Process audio files using FFmpeg
     */
    public function processAudio(
        string $inputPath,
        string $outputPath,
        ProcessingConfigInterface $config
    ): ProcessingResult {
        // Ensure we have an audio processing config
        if (!$config instanceof AudioProcessingConfig) {
            $config = new AudioProcessingConfig();
        }

        return $this->ffmpegProcessor->processAudio($inputPath, $outputPath, $config);
    }

    /**
     * Generate thumbnails for any media type
     */
    public function generateThumbnails(
        string $inputPath,
        array $sizes = [150, 300, 600],
        string $format = 'webp',
        int $quality = 85
    ): ProcessingResult {
        $mimeType = $this->detectMimeType($inputPath);
        $thumbnails = [];
        $errors = [];

        foreach ($sizes as $size) {
            $thumbnailPath = $this->generateThumbnailPath($inputPath, $size, $format);

            try {
                if ($this->isImageType($mimeType)) {
                    $config = new ImageProcessingConfig(
                        $size,
                        $size,
                        $quality,
                        $format,
                        true
                    );
                    $result = $this->processImage($inputPath, $thumbnailPath, $config);
                    
                } elseif ($this->isVideoType($mimeType)) {
                    $result = $this->ffmpegProcessor->extractVideoFrame(
                        $inputPath,
                        $thumbnailPath,
                        1.0,
                        $size,
                        $size
                    );
                    
                } else {
                    $errors[] = "Thumbnail generation not supported for type: {$mimeType}";
                    continue;
                }

                if ($result->isSuccess()) {
                    $thumbnails[$size] = $thumbnailPath;
                } else {
                    $errors[] = "Failed to generate {$size}px thumbnail: " . $result->getErrorMessage();
                }

            } catch (\Exception $e) {
                $errors[] = "Exception generating {$size}px thumbnail: " . $e->getMessage();
            }
        }

        if (empty($thumbnails) && !empty($errors)) {
            return ProcessingResult::failure(
                'Failed to generate any thumbnails',
                ['errors' => $errors]
            );
        }

        return ProcessingResult::success(
            '',
            [
                'thumbnails' => $thumbnails,
                'errors' => $errors,
                'generated_count' => count($thumbnails)
            ]
        );
    }

    /**
     * Extract metadata from any media type
     */
    public function extractMetadata(string $filePath): array
    {
        try {
            $mimeType = $this->detectMimeType($filePath);
            $basicInfo = [
                'file_path' => $filePath,
                'file_size' => filesize($filePath),
                'mime_type' => $mimeType,
                'modified_time' => filemtime($filePath)
            ];

            if ($this->isImageType($mimeType)) {
                $imageInfo = $this->imageMagickProcessor->extractMetadata($filePath);
                return array_merge($basicInfo, $imageInfo);
                
            } elseif ($this->isVideoType($mimeType) || $this->isAudioType($mimeType)) {
                $mediaInfo = $this->ffmpegProcessor->extractMetadata($filePath);
                return array_merge($basicInfo, $mediaInfo);
            }

            return $basicInfo;

        } catch (\Exception $e) {
            $this->logger->error('Failed to extract metadata', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);

            return [
                'file_path' => $filePath,
                'error' => 'Failed to extract metadata: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Convert between different media formats
     */
    public function convertFormat(
        string $inputPath,
        string $outputPath,
        string $targetFormat,
        array $options = []
    ): ProcessingResult {
        $mimeType = $this->detectMimeType($inputPath);
        
        if ($this->isImageType($mimeType)) {
            $config = new ImageProcessingConfig(
                null,
                null,
                $options['quality'] ?? 85,
                $targetFormat
            );
            return $this->processImage($inputPath, $outputPath, $config);
            
        } elseif ($this->isVideoType($mimeType)) {
            $config = new VideoProcessingConfig(
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                $targetFormat
            );
            return $this->processVideo($inputPath, $outputPath, $config);
            
        } elseif ($this->isAudioType($mimeType)) {
            $config = new AudioProcessingConfig(
                null,
                isset($options['bitrate']) ? (int)$options['bitrate'] : 192000,
                null,
                null,
                null,
                null,
                $targetFormat
            );
            return $this->processAudio($inputPath, $outputPath, $config);
        }

        return ProcessingResult::failure(
            "Format conversion not supported for type: {$mimeType}"
        );
    }

    /**
     * Process SVG files with special handling
     */
    private function processSvg(
        string $inputPath,
        string $outputPath,
        ImageProcessingConfig $config
    ): ProcessingResult {
        try {
            // Use SVG renderer service for high-quality rasterization
            if ($config->getFormat() && $config->getFormat() !== 'svg') {
                return $this->svgRendererService->rasterizeSvg(
                    $inputPath,
                    $outputPath,
                    $config->getWidth(),
                    $config->getHeight(),
                    $config->getFormat(),
                    $config->getQuality()
                );
            }

            // For SVG-to-SVG processing, use ImageMagick
            return $this->imageMagickProcessor->processImage($inputPath, $outputPath, $config);

        } catch (\Exception $e) {
            $this->logger->error('SVG processing failed', [
                'input_path' => $inputPath,
                'output_path' => $outputPath,
                'error' => $e->getMessage()
            ]);

            return ProcessingResult::failure(
                'SVG processing failed: ' . $e->getMessage()
            );
        }
    }

    private function detectMimeType(string $filePath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mimeType ?: 'application/octet-stream';
    }

    private function isImageType(string $mimeType): bool
    {
        return in_array($mimeType, self::IMAGE_TYPES, true);
    }

    private function isVideoType(string $mimeType): bool
    {
        return in_array($mimeType, self::VIDEO_TYPES, true);
    }

    private function isAudioType(string $mimeType): bool
    {
        return in_array($mimeType, self::AUDIO_TYPES, true);
    }

    private function generateThumbnailPath(string $originalPath, int $size, string $format): string
    {
        $pathInfo = pathinfo($originalPath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        
        return $directory . '/' . $filename . '_thumb_' . $size . '.' . $format;
    }
}
