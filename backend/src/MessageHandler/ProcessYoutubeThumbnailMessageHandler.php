<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\User;
use App\Message\ProcessYoutubeThumbnailMessage;
use App\Service\Plugin\Plugins\YoutubeThumbnailPlugin;
use App\Service\Plugin\Config\PluginConfigLoader;
use App\Service\MediaProcessing\AsyncMediaProcessingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles YouTube thumbnail generation in the background
 */
#[AsMessageHandler]
class ProcessYoutubeThumbnailMessageHandler
{
    public function __construct(
        private readonly YoutubeThumbnailPlugin $youtubeThumbnailPlugin,
        private readonly PluginConfigLoader $configLoader,
        private readonly AsyncMediaProcessingService $asyncService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(ProcessYoutubeThumbnailMessage $message): void
    {
        $jobId = $message->jobId;
        
        try {
            // Initialize plugin config
            try {
                $config = $this->configLoader->loadConfig('youtube_thumbnail');
                $this->youtubeThumbnailPlugin->setConfig($config);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to load config for YouTube Thumbnail plugin, using defaults', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Update job status to processing
            $this->asyncService->updateJobStatus($jobId, [
                'status' => 'processing',
                'message' => 'Starting YouTube thumbnail generation',
                'progress' => 0,
                'current_variation' => 0,
                'total_variations' => $message->thumbnailCount,
                'generation_method' => 'determining...'
            ]);

            // Get user entity
            $user = $this->entityManager->getRepository(User::class)->find($message->userId);
            if (!$user) {
                throw new \RuntimeException('User not found');
            }

            // Set up progress callback for the plugin
            $this->youtubeThumbnailPlugin->setAsyncJobId($jobId);

            // Execute the thumbnail generation
            $result = $this->youtubeThumbnailPlugin->executeCommand($user, null, 'generate_thumbnail_variations', [
                'video_url' => $message->videoUrl,
                'thumbnail_count' => $message->thumbnailCount,
                'style' => $message->style,
                'custom_prompt' => $message->customPrompt
            ], [
                'async_job_id' => $jobId // Pass job ID to plugin for progress tracking
            ]);

            // Update final status
            $this->asyncService->updateJobStatus($jobId, [
                'status' => 'completed',
                'message' => 'YouTube thumbnail generation completed',
                'progress' => 100,
                'result' => $result,
                'completed_at' => date('c')
            ]);

            $this->logger->info('YouTube thumbnail generation job completed', [
                'job_id' => $jobId,
                'user_id' => $message->userId,
                'thumbnail_count' => count($result['thumbnail_variations'] ?? [])
            ]);

        } catch (\Exception $e) {
            $this->asyncService->updateJobStatus($jobId, [
                'status' => 'failed',
                'message' => 'YouTube thumbnail generation failed: ' . $e->getMessage(),
                'progress' => 0,
                'error' => $e->getMessage(),
                'failed_at' => date('c')
            ]);

            $this->logger->error('YouTube thumbnail generation job failed', [
                'job_id' => $jobId,
                'user_id' => $message->userId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
