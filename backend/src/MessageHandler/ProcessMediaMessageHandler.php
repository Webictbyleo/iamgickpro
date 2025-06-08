<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Service\MediaProcessing\AsyncMediaProcessingService;
use App\Service\MediaProcessing\MediaProcessingService;
use App\Service\MediaProcessing\ProcessMediaMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

/**
 * Message Handler for Processing Media Files
 * 
 * Handles background processing of media files dispatched via Symfony Messenger.
 * Updates job status and handles errors during processing.
 */
#[AsMessageHandler]
class ProcessMediaMessageHandler
{
    public function __construct(
        private readonly MediaProcessingService $mediaProcessingService,
        private readonly AsyncMediaProcessingService $asyncService,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(ProcessMediaMessage $message): void
    {
        $jobId = $message->jobId;
        
        try {
            $this->logger->info('Starting media processing job', [
                'job_id' => $jobId,
                'input_path' => $message->inputPath,
                'output_path' => $message->outputPath
            ]);

            // Update job status to processing
            $this->asyncService->updateJobStatus($jobId, [
                'status' => 'processing',
                'message' => 'Processing started',
                'progress' => 0
            ]);

            // Check if job was cancelled
            $status = $this->asyncService->getJobStatus($jobId);
            if ($status['status'] === 'cancelled') {
                $this->logger->info('Job was cancelled, skipping processing', [
                    'job_id' => $jobId
                ]);
                return;
            }

            // Process the media file
            $result = $this->mediaProcessingService->processMedia(
                inputPath: $message->inputPath,
                outputPath: $message->outputPath,
                config: $message->config
            );

            if ($result->isSuccess()) {
                $this->asyncService->updateJobStatus($jobId, [
                    'status' => 'completed',
                    'message' => 'Processing completed successfully',
                    'progress' => 100,
                    'output_path' => $result->getOutputPath(),
                    'metadata' => $result->getMetadata()
                ]);

                $this->logger->info('Media processing job completed successfully', [
                    'job_id' => $jobId,
                    'output_path' => $result->getOutputPath()
                ]);
            } else {
                $this->asyncService->updateJobStatus($jobId, [
                    'status' => 'failed',
                    'message' => $result->getErrorMessage(),
                    'progress' => 0,
                    'metadata' => $result->getMetadata()
                ]);

                $this->logger->error('Media processing job failed', [
                    'job_id' => $jobId,
                    'error' => $result->getErrorMessage()
                ]);
            }

        } catch (\Exception $e) {
            $this->asyncService->updateJobStatus($jobId, [
                'status' => 'failed',
                'message' => 'Processing failed: ' . $e->getMessage(),
                'progress' => 0,
                'exception' => $e::class
            ]);

            $this->logger->error('Media processing job failed with exception', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'exception' => $e::class,
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw to let Messenger handle retry logic
        }
    }
}
