<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ExportDesignMessage;
use App\Repository\ExportJobRepository;
use App\Service\ExportService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
final readonly class ExportDesignMessageHandler
{
    public function __construct(
        private ExportService $exportService,
        private ExportJobRepository $exportJobRepository,
        private LoggerInterface $logger
    ) {}

    public function __invoke(ExportDesignMessage $message): void
    {
        try {
            $this->logger->info('Processing export job', [
                'export_job_id' => $message->exportJobId,
                'design_id' => $message->designId,
                'format' => $message->format
            ]);

            $exportJob = $this->exportJobRepository->find($message->exportJobId);
            if (!$exportJob) {
                throw new \RuntimeException('Export job not found: ' . $message->exportJobId);
            }

            $this->exportService->processExportJob($exportJob);

            $this->logger->info('Export job completed successfully', [
                'export_job_id' => $message->exportJobId
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Export job failed', [
                'export_job_id' => $message->exportJobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update export job status to failed
            $exportJob = $this->exportJobRepository->find($message->exportJobId);
            if ($exportJob) {
                $this->exportService->markJobAsFailed($exportJob, $e->getMessage());
            }
            
            throw $e;
        }
    }
}
