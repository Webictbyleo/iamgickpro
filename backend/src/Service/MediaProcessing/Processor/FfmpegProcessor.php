<?php

declare(strict_types=1);

namespace App\Service\MediaProcessing\Processor;

use App\Service\MediaProcessing\Config\VideoProcessingConfig;
use App\Service\MediaProcessing\Config\AudioProcessingConfig;
use App\Service\MediaProcessing\Result\ProcessingResult;
use Psr\Log\LoggerInterface;

/**
 * FFmpeg processor for video and audio manipulation
 */
readonly class FfmpegProcessor
{
    public function __construct(
        private string $ffmpegPath,
        private string $ffprobePath,
        private LoggerInterface $logger,
        private int $timeLimit = 300
    ) {}

    public function processVideo(
        string $inputPath,
        string $outputPath,
        VideoProcessingConfig $config
    ): ProcessingResult {
        $startTime = microtime(true);

        try {
            if (!file_exists($inputPath)) {
                return ProcessingResult::failure('Input file does not exist: ' . $inputPath);
            }

            $command = $this->buildVideoCommand($inputPath, $outputPath, $config);
            
            $this->logger->info('Executing FFmpeg video command', [
                'command' => $command,
                'input' => $inputPath,
                'output' => $outputPath
            ]);

            $result = $this->executeCommand($command);
            $processingTime = microtime(true) - $startTime;

            if ($result['success']) {
                $metadata = $this->extractVideoMetadata($outputPath);
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
            $this->logger->error('FFmpeg video processing failed', [
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

    public function processAudio(
        string $inputPath,
        string $outputPath,
        AudioProcessingConfig $config
    ): ProcessingResult {
        $startTime = microtime(true);

        try {
            if (!file_exists($inputPath)) {
                return ProcessingResult::failure('Input file does not exist: ' . $inputPath);
            }

            $command = $this->buildAudioCommand($inputPath, $outputPath, $config);
            
            $this->logger->info('Executing FFmpeg audio command', [
                'command' => $command,
                'input' => $inputPath,
                'output' => $outputPath
            ]);

            $result = $this->executeCommand($command);
            $processingTime = microtime(true) - $startTime;

            if ($result['success']) {
                $metadata = $this->extractAudioMetadata($outputPath);
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
            $this->logger->error('FFmpeg audio processing failed', [
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

    public function extractVideoFrame(
        string $videoPath,
        string $outputPath,
        float $timestamp = 0.0,
        ?int $width = null,
        ?int $height = null
    ): ProcessingResult {
        $startTime = microtime(true);

        try {
            $command = [
                escapeshellarg($this->ffmpegPath),
                '-i', escapeshellarg($videoPath),
                '-ss', (string) $timestamp,
                '-vframes', '1',
                '-y'
            ];

            if ($width && $height) {
                $command[] = '-vf';
                $command[] = escapeshellarg("scale={$width}:{$height}");
            }

            $command[] = escapeshellarg($outputPath);

            $cmdString = implode(' ', $command);
            $result = $this->executeCommand($cmdString);
            $processingTime = microtime(true) - $startTime;

            if ($result['success']) {
                return ProcessingResult::success(
                    outputPath: $outputPath,
                    metadata: ['timestamp' => $timestamp],
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

    public function generateVideoThumbnails(
        string $videoPath,
        string $outputDirectory,
        int $count = 5,
        ?int $width = null,
        ?int $height = null
    ): ProcessingResult {
        $startTime = microtime(true);
        $processedFiles = [];

        try {
            // Get video duration first
            $metadata = $this->extractVideoMetadata($videoPath);
            $duration = $metadata['duration'] ?? 0;

            if ($duration <= 0) {
                return ProcessingResult::failure('Could not determine video duration');
            }

            $interval = $duration / ($count + 1);

            for ($i = 1; $i <= $count; $i++) {
                $timestamp = $interval * $i;
                $outputPath = $outputDirectory . '/' . pathinfo($videoPath, PATHINFO_FILENAME) . "_thumb_{$i}.jpg";
                
                $result = $this->extractVideoFrame($videoPath, $outputPath, $timestamp, $width, $height);
                
                if ($result->isSuccess()) {
                    $processedFiles["thumb_{$i}"] = $outputPath;
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

    /**
     * Extract metadata from media file (video or audio)
     */
    public function extractMetadata(string $filePath): array
    {
        // Use ffprobe to determine if it's video or audio-only
        try {
            $probeCommand = sprintf(
                '%s -v quiet -show_entries stream=codec_type -of csv=p=0 %s',
                escapeshellarg($this->ffprobePath),
                escapeshellarg($filePath)
            );
            
            $output = shell_exec($probeCommand);
            $streamTypes = array_filter(explode("\n", trim($output ?? '')));
            
            // If we have video streams, use video metadata extraction
            if (in_array('video', $streamTypes)) {
                return $this->extractVideoMetadata($filePath);
            } else {
                // Audio-only file
                return $this->extractAudioMetadata($filePath);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to determine media type for metadata extraction', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to video metadata extraction
            return $this->extractVideoMetadata($filePath);
        }
    }

    private function buildVideoCommand(
        string $inputPath,
        string $outputPath,
        VideoProcessingConfig $config
    ): string {
        $cmd = [
            escapeshellarg($this->ffmpegPath),
            '-i', escapeshellarg($inputPath)
        ];

        // Start time
        if ($config->getStartTime() !== null) {
            $cmd[] = '-ss';
            $cmd[] = (string) $config->getStartTime();
        }

        // Duration
        if ($config->getDuration() !== null) {
            $cmd[] = '-t';
            $cmd[] = (string) $config->getDuration();
        }

        // Video codec
        if ($config->getCodec()) {
            $cmd[] = '-c:v';
            $cmd[] = escapeshellarg($config->getCodec());
        }

        // Video filters
        $videoFilters = [];

        // Scaling
        if ($config->hasResize()) {
            $scaleFilter = $this->buildScaleFilter($config);
            if ($scaleFilter) {
                $videoFilters[] = $scaleFilter;
            }
        }

        // Custom filters
        foreach ($config->getFilters() as $filter) {
            $videoFilters[] = $filter;
        }

        if (!empty($videoFilters)) {
            $cmd[] = '-vf';
            $cmd[] = escapeshellarg(implode(',', $videoFilters));
        }

        // Video bitrate
        if ($config->getBitrate()) {
            $cmd[] = '-b:v';
            $cmd[] = $config->getBitrate() . 'k';
        }

        // Framerate
        if ($config->getFramerate()) {
            $cmd[] = '-r';
            $cmd[] = (string) $config->getFramerate();
        }

        // Audio codec
        if ($config->getAudioCodec()) {
            $cmd[] = '-c:a';
            $cmd[] = escapeshellarg($config->getAudioCodec());
        } elseif ($config->getAudioCodec() === null) {
            // Copy audio if not specified
            $cmd[] = '-c:a';
            $cmd[] = 'copy';
        }

        // Audio bitrate
        if ($config->getAudioBitrate()) {
            $cmd[] = '-b:a';
            $cmd[] = $config->getAudioBitrate() . 'k';
        }

        // Audio sample rate
        if ($config->getAudioSampleRate()) {
            $cmd[] = '-ar';
            $cmd[] = (string) $config->getAudioSampleRate();
        }

        // Custom options
        foreach ($config->getCustomOptions() as $option => $value) {
            $cmd[] = '-' . $option;
            if ($value !== null) {
                $cmd[] = escapeshellarg((string) $value);
            }
        }

        // Overwrite output
        $cmd[] = '-y';

        // Output file
        $cmd[] = escapeshellarg($outputPath);

        return implode(' ', $cmd);
    }

    private function buildAudioCommand(
        string $inputPath,
        string $outputPath,
        AudioProcessingConfig $config
    ): string {
        $cmd = [
            escapeshellarg($this->ffmpegPath),
            '-i', escapeshellarg($inputPath)
        ];

        // Start time
        if ($config->getStartTime() !== null) {
            $cmd[] = '-ss';
            $cmd[] = (string) $config->getStartTime();
        }

        // Duration
        if ($config->getDuration() !== null) {
            $cmd[] = '-t';
            $cmd[] = (string) $config->getDuration();
        }

        // Audio codec
        if ($config->getCodec()) {
            $cmd[] = '-c:a';
            $cmd[] = escapeshellarg($config->getCodec());
        }

        // Audio bitrate
        if ($config->getBitrate()) {
            $cmd[] = '-b:a';
            $cmd[] = $config->getBitrate() . 'k';
        }

        // Sample rate
        if ($config->getSampleRate()) {
            $cmd[] = '-ar';
            $cmd[] = (string) $config->getSampleRate();
        }

        // Channels
        if ($config->getChannels()) {
            $cmd[] = '-ac';
            $cmd[] = (string) $config->getChannels();
        }

        // Audio filters
        $audioFilters = [];

        // Volume
        if ($config->getVolume() !== null) {
            $audioFilters[] = 'volume=' . $config->getVolume();
        }

        // Normalize
        if ($config->shouldNormalize()) {
            $audioFilters[] = 'loudnorm';
        }

        // Custom filters
        foreach ($config->getFilters() as $filter) {
            $audioFilters[] = $filter;
        }

        if (!empty($audioFilters)) {
            $cmd[] = '-af';
            $cmd[] = escapeshellarg(implode(',', $audioFilters));
        }

        // Custom options
        foreach ($config->getCustomOptions() as $option => $value) {
            $cmd[] = '-' . $option;
            if ($value !== null) {
                $cmd[] = escapeshellarg((string) $value);
            }
        }

        // No video
        $cmd[] = '-vn';

        // Overwrite output
        $cmd[] = '-y';

        // Output file
        $cmd[] = escapeshellarg($outputPath);

        return implode(' ', $cmd);
    }

    private function buildScaleFilter(VideoProcessingConfig $config): ?string
    {
        $width = $config->getWidth();
        $height = $config->getHeight();

        if (!$width && !$height) {
            return null;
        }

        if ($config->shouldMaintainAspectRatio()) {
            if ($width && $height) {
                return "scale='{$width}:{$height}:force_original_aspect_ratio=decrease'";
            } elseif ($width) {
                return "scale='{$width}:-1'";
            } else {
                return "scale='-1:{$height}'";
            }
        } else {
            $w = $width ?? -1;
            $h = $height ?? -1;
            return "scale='{$w}:{$h}'";
        }
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

    private function extractVideoMetadata(string $videoPath): array
    {
        try {
            $command = sprintf(
                '%s -v quiet -print_format json -show_format -show_streams %s',
                escapeshellarg($this->ffprobePath),
                escapeshellarg($videoPath)
            );

            $output = shell_exec($command);
            if (!$output) {
                return [];
            }

            $data = json_decode($output, true);
            if (!$data) {
                return [];
            }

            $videoStream = null;
            $audioStream = null;

            foreach ($data['streams'] ?? [] as $stream) {
                if ($stream['codec_type'] === 'video' && !$videoStream) {
                    $videoStream = $stream;
                } elseif ($stream['codec_type'] === 'audio' && !$audioStream) {
                    $audioStream = $stream;
                }
            }

            $metadata = [
                'format' => $data['format']['format_name'] ?? null,
                'duration' => (float) ($data['format']['duration'] ?? 0),
                'size' => (int) ($data['format']['size'] ?? 0),
                'bitrate' => (int) ($data['format']['bit_rate'] ?? 0),
            ];

            if ($videoStream) {
                $metadata['video'] = [
                    'codec' => $videoStream['codec_name'] ?? null,
                    'width' => (int) ($videoStream['width'] ?? 0),
                    'height' => (int) ($videoStream['height'] ?? 0),
                    'framerate' => $this->parseFramerate($videoStream['r_frame_rate'] ?? '0/1'),
                    'bitrate' => (int) ($videoStream['bit_rate'] ?? 0),
                ];
            }

            if ($audioStream) {
                $metadata['audio'] = [
                    'codec' => $audioStream['codec_name'] ?? null,
                    'channels' => (int) ($audioStream['channels'] ?? 0),
                    'sample_rate' => (int) ($audioStream['sample_rate'] ?? 0),
                    'bitrate' => (int) ($audioStream['bit_rate'] ?? 0),
                ];
            }

            return $metadata;

        } catch (\Exception $e) {
            $this->logger->warning('Failed to extract video metadata', [
                'path' => $videoPath,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function extractAudioMetadata(string $audioPath): array
    {
        $metadata = $this->extractVideoMetadata($audioPath);
        
        // Return only audio-relevant metadata
        return [
            'format' => $metadata['format'] ?? null,
            'duration' => $metadata['duration'] ?? 0,
            'size' => $metadata['size'] ?? 0,
            'bitrate' => $metadata['bitrate'] ?? 0,
            'audio' => $metadata['audio'] ?? []
        ];
    }

    private function parseFramerate(string $framerate): float
    {
        if (str_contains($framerate, '/')) {
            [$num, $den] = explode('/', $framerate);
            return $den > 0 ? (float) $num / (float) $den : 0.0;
        }
        
        return (float) $framerate;
    }
}
