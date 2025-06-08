<?php

declare(strict_types=1);

namespace App\Service\MediaProcessing;

use App\Service\MediaProcessing\Config\ProcessingConfigInterface;
use App\Service\MediaProcessing\Result\ProcessingResult;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Psr\Log\LoggerInterface;

/**
 * Async Media Processing Service
 * 
 * Handles background processing of media files using Symfony Messenger.
 * Provides job queuing, progress tracking, and result management for
 * long-running media processing operations.
 */
class AsyncMediaProcessingService
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly string $projectDir,
    ) {}

    /**
     * Queue a media processing job for background execution
     */
    public function queueProcessing(
        string $inputPath,
        string $outputPath,
        ProcessingConfigInterface $config,
        ?int $delaySeconds = null
    ): ProcessingResult {
        try {
            $jobId = $this->generateJobId();
            
            // Create processing message
            $message = new ProcessMediaMessage(
                jobId: $jobId,
                inputPath: $inputPath,
                outputPath: $outputPath,
                config: $config
            );

            // Add delay stamp if specified
            $stamps = [];
            if ($delaySeconds !== null) {
                $stamps[] = new DelayStamp($delaySeconds * 1000); // Convert to milliseconds
            }

            // Dispatch the message
            $this->messageBus->dispatch($message, $stamps);

            $this->logger->info('Media processing job queued', [
                'job_id' => $jobId,
                'input_path' => $inputPath,
                'output_path' => $outputPath,
                'config_type' => $config::class,
                'delay_seconds' => $delaySeconds
            ]);

            return ProcessingResult::success(
                outputPath: $outputPath,
                metadata: ['job_id' => $jobId, 'status' => 'queued'],
                jobId: $jobId
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to queue media processing job', [
                'input_path' => $inputPath,
                'output_path' => $outputPath,
                'error' => $e->getMessage()
            ]);

            return ProcessingResult::failure(
                errorMessage: 'Failed to queue processing job: ' . $e->getMessage(),
                metadata: ['exception' => $e::class]
            );
        }
    }

    /**
     * Get the status of a background processing job
     */
    public function getJobStatus(string $jobId): array
    {
        $statusFile = $this->getJobStatusFile($jobId);
        
        if (!file_exists($statusFile)) {
            return [
                'status' => 'not_found',
                'message' => 'Job not found'
            ];
        }

        $statusData = json_decode(file_get_contents($statusFile), true);
        return $statusData ?: [
            'status' => 'unknown',
            'message' => 'Could not read job status'
        ];
    }

    /**
     * Update the status of a background processing job
     */
    public function updateJobStatus(string $jobId, array $status): void
    {
        $statusFile = $this->getJobStatusFile($jobId);
        $statusDir = dirname($statusFile);

        if (!is_dir($statusDir)) {
            mkdir($statusDir, 0755, true);
        }

        file_put_contents($statusFile, json_encode([
            'job_id' => $jobId,
            'updated_at' => date('c'),
            ...$status
        ]));
    }

    /**
     * Cancel a background processing job
     */
    public function cancelJob(string $jobId): bool
    {
        try {
            $this->updateJobStatus($jobId, [
                'status' => 'cancelled',
                'message' => 'Job cancelled by user'
            ]);

            $this->logger->info('Media processing job cancelled', [
                'job_id' => $jobId
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to cancel media processing job', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Clean up completed job files older than specified days
     */
    public function cleanupOldJobs(int $daysOld = 7): int
    {
        $jobsDir = $this->projectDir . '/var/processing_jobs';
        if (!is_dir($jobsDir)) {
            return 0;
        }

        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        $cleaned = 0;

        foreach (glob($jobsDir . '/*.json') as $statusFile) {
            if (filemtime($statusFile) < $cutoffTime) {
                unlink($statusFile);
                $cleaned++;
            }
        }

        $this->logger->info('Cleaned up old processing jobs', [
            'cleaned_count' => $cleaned,
            'days_old' => $daysOld
        ]);

        return $cleaned;
    }

    private function generateJobId(): string
    {
        return 'job_' . uniqid() . '_' . time();
    }

    private function getJobStatusFile(string $jobId): string
    {
        return $this->projectDir . '/var/processing_jobs/' . $jobId . '.json';
    }
}


