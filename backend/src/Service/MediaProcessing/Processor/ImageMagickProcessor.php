<?php

declare(strict_types=1);

namespace App\Service\MediaProcessing\Processor;

use App\Service\MediaProcessing\Config\ImageProcessingConfig;
use App\Service\MediaProcessing\Result\ProcessingResult;
use Psr\Log\LoggerInterface;

/**
 * ImageMagick 7 processor for advanced image manipulation
 */
readonly class ImageMagickProcessor
{
    public function __construct(
        private string $imageMagickPath,
        private LoggerInterface $logger,
        private int $memoryLimit = 256,
        private int $timeLimit = 60
    ) {}

    public function processImage(
        string $inputPath,
        string $outputPath,
        ImageProcessingConfig $config
    ): ProcessingResult {
        $startTime = microtime(true);

        try {
            if (!file_exists($inputPath)) {
                return ProcessingResult::failure('Input file does not exist: ' . $inputPath);
            }

            $command = $this->buildImageMagickCommand($inputPath, $outputPath, $config);
            
            $this->logger->info('Executing ImageMagick command', [
                'command' => $command,
                'input' => $inputPath,
                'output' => $outputPath
            ]);

            $result = $this->executeCommand($command);
            $processingTime = microtime(true) - $startTime;

            if ($result['success']) {
                $metadata = $this->extractImageMetadata($outputPath);
                return ProcessingResult::success(
                    outputPath: $outputPath,
                    metadata: $metadata,
                    processingTime: $processingTime
                );
            } else {
                return ProcessingResult::failure(
                    errorMessage: $result['error'],
                    processingTime: $processingTime
                );
            }

        } catch (\Exception $e) {
            $processingTime = microtime(true) - $startTime;
            $this->logger->error('ImageMagick processing failed', [
                'input' => $inputPath,
                'output' => $outputPath,
                'error' => $e->getMessage()
            ]);

            return ProcessingResult::failure(
                errorMessage: $e->getMessage(),
                processingTime: $processingTime
            );
        }
    }

    public function createThumbnails(
        string $inputPath,
        array $sizes,
        string $outputDirectory,
        ?ImageProcessingConfig $baseConfig = null
    ): ProcessingResult {
        $startTime = microtime(true);
        $processedFiles = [];

        try {
            if (!file_exists($inputPath)) {
                return ProcessingResult::failure('Input file does not exist: ' . $inputPath);
            }

            foreach ($sizes as $size) {
                [$width, $height] = $this->parseSizeString($size);
                
                $config = new ImageProcessingConfig(
                    width: $width,
                    height: $height,
                    quality: $baseConfig?->getQuality() ?? 85,
                    format: $baseConfig?->getFormat() ?? 'jpg',
                    maintainAspectRatio: true,
                    preserveTransparency: $baseConfig?->shouldPreserveTransparency() ?? true
                );

                $outputPath = $outputDirectory . '/' . $this->generateThumbnailFilename($inputPath, $size);
                $result = $this->processImage($inputPath, $outputPath, $config);

                if ($result->isSuccess()) {
                    $processedFiles[$size] = $outputPath;
                } else {
                    $this->logger->warning('Failed to create thumbnail', [
                        'size' => $size,
                        'error' => $result->getErrorMessage()
                    ]);
                }
            }

            $processingTime = microtime(true) - $startTime;

            return ProcessingResult::success(
                outputPath: $outputDirectory,
                processedFiles: $processedFiles,
                processingTime: $processingTime
            );

        } catch (\Exception $e) {
            $processingTime = microtime(true) - $startTime;
            return ProcessingResult::failure(
                errorMessage: $e->getMessage(),
                processingTime: $processingTime
            );
        }
    }

    public function rasterizeSvg(
        string $svgPath,
        string $outputPath,
        ImageProcessingConfig $config
    ): ProcessingResult {
        $startTime = microtime(true);

        try {
            // Special handling for SVG rasterization
            $command = $this->buildSvgRasterizationCommand($svgPath, $outputPath, $config);
            
            $this->logger->info('Rasterizing SVG', [
                'command' => $command,
                'svg' => $svgPath,
                'output' => $outputPath
            ]);

            $result = $this->executeCommand($command);
            $processingTime = microtime(true) - $startTime;

            if ($result['success']) {
                $metadata = $this->extractImageMetadata($outputPath);
                return ProcessingResult::success(
                    outputPath: $outputPath,
                    metadata: $metadata,
                    processingTime: $processingTime
                );
            } else {
                return ProcessingResult::failure(
                    errorMessage: $result['error'],
                    processingTime: $processingTime
                );
            }

        } catch (\Exception $e) {
            $processingTime = microtime(true) - $startTime;
            return ProcessingResult::failure(
                errorMessage: $e->getMessage(),
                processingTime: $processingTime
            );
        }
    }

    /**
     * Extract metadata from image file
     */
    public function extractMetadata(string $imagePath): array
    {
        return $this->extractImageMetadata($imagePath);
    }

    private function buildImageMagickCommand(
        string $inputPath,
        string $outputPath,
        ImageProcessingConfig $config
    ): string {
        $cmd = [
            escapeshellarg($this->imageMagickPath),
            '-limit', 'memory', $this->memoryLimit . 'MB',
            '-limit', 'time', $this->timeLimit,
        ];

        // Input file
        $cmd[] = escapeshellarg($inputPath);

        // Preserve transparency handling
        if ($config->shouldPreserveTransparency()) {
            $cmd[] = '-background';
            $cmd[] = 'transparent';
        } elseif ($config->getBackgroundColor()) {
            $cmd[] = '-background';
            $cmd[] = escapeshellarg($config->getBackgroundColor());
            $cmd[] = '-flatten';
        }

        // Resize operations
        if ($config->hasResize()) {
            $geometry = $this->buildGeometryString($config);
            $cmd[] = '-resize';
            $cmd[] = escapeshellarg($geometry);
        }

        // Quality setting
        if ($config->hasQuality()) {
            $cmd[] = '-quality';
            $cmd[] = (string) $config->getQuality();
        }

        // Strip metadata
        if ($config->shouldStripMetadata()) {
            $cmd[] = '-strip';
        }

        // Progressive JPEG
        if ($config->shouldBeProgressive() && in_array($config->getFormat(), ['jpg', 'jpeg'])) {
            $cmd[] = '-interlace';
            $cmd[] = 'Plane';
        }

        // Color space
        if ($config->getColorSpace()) {
            $cmd[] = '-colorspace';
            $cmd[] = escapeshellarg($config->getColorSpace());
        }

        // Apply filters
        foreach ($config->getFilters() as $filter => $params) {
            $cmd = array_merge($cmd, $this->buildFilterCommand($filter, $params));
        }

        // Custom options
        foreach ($config->getCustomOptions() as $option => $value) {
            $cmd[] = '-' . $option;
            if ($value !== null) {
                $cmd[] = escapeshellarg((string) $value);
            }
        }

        // Output file
        $cmd[] = escapeshellarg($outputPath);

        return implode(' ', $cmd);
    }

    private function buildSvgRasterizationCommand(
        string $svgPath,
        string $outputPath,
        ImageProcessingConfig $config
    ): string {
        $cmd = [
            escapeshellarg($this->imageMagickPath),
            '-limit', 'memory', $this->memoryLimit . 'MB',
            '-limit', 'time', $this->timeLimit,
            '-background', 'transparent',
        ];

        // Set density for high-quality rasterization
        $density = 300; // DPI
        if ($config->hasResize()) {
            // Calculate optimal density based on target size
            $density = max(150, min(600, ($config->getWidth() ?? $config->getHeight() ?? 300) * 2));
        }

        $cmd[] = '-density';
        $cmd[] = (string) $density;

        // Input SVG
        $cmd[] = escapeshellarg($svgPath);

        // Resize if specified
        if ($config->hasResize()) {
            $geometry = $this->buildGeometryString($config);
            $cmd[] = '-resize';
            $cmd[] = escapeshellarg($geometry);
        }

        // Quality
        if ($config->hasQuality()) {
            $cmd[] = '-quality';
            $cmd[] = (string) $config->getQuality();
        }

        // Flatten for non-transparent formats
        if (!$config->shouldPreserveTransparency()) {
            $cmd[] = '-flatten';
        }

        // Output file
        $cmd[] = escapeshellarg($outputPath);

        return implode(' ', $cmd);
    }

    private function buildGeometryString(ImageProcessingConfig $config): string
    {
        $width = $config->getWidth();
        $height = $config->getHeight();

        if ($width && $height) {
            $geometry = $width . 'x' . $height;
            if (!$config->shouldMaintainAspectRatio()) {
                $geometry .= '!';
            }
        } elseif ($width) {
            $geometry = $width . 'x';
        } elseif ($height) {
            $geometry = 'x' . $height;
        } else {
            $geometry = '';
        }

        return $geometry;
    }

    private function buildFilterCommand(string $filter, array $params): array
    {
        return match ($filter) {
            'blur' => ['-blur', escapeshellarg($params['radius'] ?? '0x1')],
            'sharpen' => ['-sharpen', escapeshellarg($params['amount'] ?? '0x1')],
            'contrast' => ['-contrast-stretch', escapeshellarg($params['black'] ?? '0') . '%x' . escapeshellarg($params['white'] ?? '0') . '%'],
            'brightness' => ['-modulate', escapeshellarg(($params['brightness'] ?? 100) . ',100,100')],
            'saturation' => ['-modulate', escapeshellarg('100,' . ($params['saturation'] ?? 100) . ',100')],
            'hue' => ['-modulate', escapeshellarg('100,100,' . ($params['hue'] ?? 100))],
            'normalize' => ['-normalize'],
            'auto-level' => ['-auto-level'],
            'auto-gamma' => ['-auto-gamma'],
            'sepia' => ['-sepia-tone', escapeshellarg($params['threshold'] ?? '80%')],
            'grayscale' => ['-colorspace', 'Gray'],
            default => []
        };
    }

    private function executeCommand(string $command): array
    {
        $output = [];
        $returnCode = 0;

        exec($command . ' 2>&1', $output, $returnCode);

        return [
            'success' => $returnCode === 0,
            'output' => implode("\n", $output),
            'error' => $returnCode !== 0 ? implode("\n", $output) : null,
            'return_code' => $returnCode
        ];
    }

    private function extractImageMetadata(string $imagePath): array
    {
        try {
            // Get basic image properties from ImageMagick
            $command = sprintf(
                '%s identify -ping -format "%%wx%%h,%%[colorspace],%%Q,%%[bit-depth],%%[orientation]" %s',
                escapeshellarg($this->imageMagickPath),
                escapeshellarg($imagePath)
            );

            $output = shell_exec($command);
            if (!$output) {
                return [];
            }

            $parts = explode(',', trim($output));
            if (count($parts) < 5) {
                return [];
            }

            [$dimensions, $colorspace, $quality, $bitDepth, $orientation] = $parts;
            [$width, $height] = explode('x', $dimensions);

            // Get filesize using PHP's built-in function (more reliable)
            $filesize = file_exists($imagePath) ? filesize($imagePath) : 0;

            return [
                'width' => (int) $width,
                'height' => (int) $height,
                'colorspace' => trim($colorspace),
                'quality' => (int) $quality,
                'bit_depth' => (int) $bitDepth,
                'filesize' => $filesize,
                'orientation' => (int) $orientation
            ];

        } catch (\Exception $e) {
            $this->logger->warning('Failed to extract image metadata', [
                'path' => $imagePath,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function parseSizeString(string $size): array
    {
        if (str_contains($size, 'x')) {
            [$width, $height] = explode('x', $size);
            return [(int) $width, (int) $height];
        }

        $dimension = (int) $size;
        return [$dimension, $dimension];
    }

    private function generateThumbnailFilename(string $originalPath, string $size): string
    {
        $info = pathinfo($originalPath);
        return $info['filename'] . '_' . $size . '.jpg';
    }
}
