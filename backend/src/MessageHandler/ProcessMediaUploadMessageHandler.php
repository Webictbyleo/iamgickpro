<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ProcessMediaUploadMessage;
use App\Repository\MediaRepository;
use App\Service\MediaService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
final readonly class ProcessMediaUploadMessageHandler
{
    public function __construct(
        private MediaService $mediaService,
        private MediaRepository $mediaRepository,
        private LoggerInterface $logger
    ) {}

    public function __invoke(ProcessMediaUploadMessage $message): void
    {
        try {
            $this->logger->info('Processing media upload', [
                'media_id' => $message->mediaId,
                'file_path' => $message->filePath
            ]);

            $media = $this->mediaRepository->find($message->mediaId);
            if (!$media) {
                throw new \RuntimeException('Media not found: ' . $message->mediaId);
            }

            // Process the uploaded media (generate thumbnails, optimize, etc.)
            $this->mediaService->processUploadedFile($media, $message->filePath, $message->metadata);

            $this->logger->info('Media upload processed successfully', [
                'media_id' => $message->mediaId
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Media upload processing failed', [
                'media_id' => $message->mediaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}
