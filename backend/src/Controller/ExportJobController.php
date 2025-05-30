<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ExportJob;
use App\Repository\ExportJobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/export-jobs', name: 'api_export_jobs_')]
class ExportJobController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ExportJobRepository $exportJobRepository,
        private readonly ValidatorInterface $validator
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $status = $request->query->get('status');
        $format = $request->query->get('format');

        $jobs = $this->exportJobRepository->findByUser(
            $this->getUser(),
            $status,
            $format,
            $page,
            $limit
        );

        $total = $this->exportJobRepository->countByUser(
            $this->getUser(),
            $status,
            $format
        );

        return $this->json([
            'jobs' => array_map([$this, 'serializeExportJob'], $jobs),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit),
            ],
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(ExportJob $exportJob): JsonResponse
    {
        // Only allow access to own export jobs
        if ($exportJob->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($this->serializeExportJob($exportJob, true));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        // Get the design entity
        $designId = $data['design_id'] ?? null;
        if (!$designId) {
            return $this->json(['error' => 'Design ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $design = $this->entityManager->getRepository(\App\Entity\Design::class)->find($designId);
        if (!$design) {
            return $this->json(['error' => 'Design not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user owns the design or has access to it
        if ($design->getUser() !== $user && !$design->isPublic()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $exportJob = new ExportJob(
            $user,
            $design,
            $data['format'] ?? 'png',
            $data['quality'] ?? 'medium',
            $data['width'] ?? null,
            $data['height'] ?? null,
            $data['scale'] ?? null,
            $data['transparent'] ?? false,
            $data['background_color'] ?? null,
            $data['animation_settings'] ?? null
        );

        $errors = $this->validator->validate($exportJob);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($exportJob);
        $this->entityManager->flush();

        // TODO: Dispatch to message queue for async processing
        // $this->messageBus->dispatch(new ProcessExportJobMessage($exportJob->getId()));

        return $this->json($this->serializeExportJob($exportJob), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(ExportJob $exportJob, Request $request): JsonResponse
    {
        // Only allow access to own export jobs
        if ($exportJob->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        // Export job properties are readonly after creation
        return $this->json(['error' => 'Export job cannot be modified after creation'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(ExportJob $exportJob): JsonResponse
    {
        // Only allow access to own export jobs
        if ($exportJob->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        // Only allow deletion of pending or failed jobs
        if (!in_array($exportJob->getStatus(), ['pending', 'failed', 'completed'])) {
            return $this->json(['error' => 'Cannot delete job that is in progress'], Response::HTTP_BAD_REQUEST);
        }

        // TODO: Clean up any generated files
        if ($exportJob->getFilePath()) {
            // Delete the output file if it exists
            $filePath = $exportJob->getFilePath();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $this->entityManager->remove($exportJob);
        $this->entityManager->flush();

        return $this->json(['message' => 'Export job deleted successfully']);
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(ExportJob $exportJob): JsonResponse
    {
        // Only allow access to own export jobs
        if ($exportJob->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        // Only allow cancellation of pending or processing jobs
        if (!in_array($exportJob->getStatus(), ['pending', 'processing'])) {
            return $this->json(['error' => 'Cannot cancel job that is not in progress'], Response::HTTP_BAD_REQUEST);
        }

        $exportJob->cancel();
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Export job cancelled successfully',
            'job' => $this->serializeExportJob($exportJob),
        ]);
    }

    #[Route('/{id}/retry', name: 'retry', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function retry(ExportJob $exportJob): JsonResponse
    {
        // Only allow access to own export jobs
        if ($exportJob->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        // Only allow retry for failed jobs
        if (!$exportJob->isFailed()) {
            return $this->json(['error' => 'Can only retry failed jobs'], Response::HTTP_BAD_REQUEST);
        }

        $exportJob->setStatus('pending');
        $exportJob->setProgress(0);
        $exportJob->setErrorMessage(null);
        $this->entityManager->flush();

        // TODO: Dispatch to message queue for async processing
        // $this->messageBus->dispatch(new ProcessExportJobMessage($exportJob->getId()));

        return $this->json([
            'message' => 'Export job queued for retry',
            'job' => $this->serializeExportJob($exportJob),
        ]);
    }

    #[Route('/{id}/download', name: 'download', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function download(ExportJob $exportJob): Response
    {
        // Only allow access to own export jobs
        if ($exportJob->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        // Only allow download of completed jobs
        if ($exportJob->getStatus() !== 'completed') {
            return $this->json(['error' => 'Export job is not completed'], Response::HTTP_BAD_REQUEST);
        }

        $filePath = $exportJob->getFilePath();
        if (!$filePath || !file_exists($filePath)) {
            return $this->json(['error' => 'Export file not found'], Response::HTTP_NOT_FOUND);
        }

        $filename = $exportJob->getFileName() ?? basename($filePath);
        $mimeType = $exportJob->getMimeType() ?? mime_content_type($filePath);

        $response = $this->file($filePath, $filename, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        $response->headers->set('Content-Type', $mimeType);
        
        return $response;
    }

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function stats(): JsonResponse
    {
        $stats = $this->exportJobRepository->getUserStats($this->getUser());

        return $this->json([
            'total_jobs' => $stats['total'],
            'completed_jobs' => $stats['completed'],
            'failed_jobs' => $stats['failed'],
            'pending_jobs' => $stats['pending'],
            'processing_jobs' => $stats['processing'],
            'format_breakdown' => $stats['by_format'],
            'success_rate' => $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100, 2) : 0,
        ]);
    }

    #[Route('/queue-status', name: 'queue_status', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function queueStatus(): JsonResponse
    {
        $queueStats = $this->exportJobRepository->getQueueStats();

        return $this->json([
            'pending_jobs' => $queueStats['pending'],
            'processing_jobs' => $queueStats['processing'],
            'failed_jobs' => $queueStats['failed'],
            'avg_processing_time' => $queueStats['avg_processing_time'],
            'queue_health' => $queueStats['queue_health'],
        ]);
    }

    private function serializeExportJob(ExportJob $exportJob, bool $detailed = false): array
    {
        $data = [
            'id' => $exportJob->getId(),
            'design_id' => $exportJob->getDesign()->getId(),
            'format' => $exportJob->getFormat(),
            'status' => $exportJob->getStatus(),
            'progress' => $exportJob->getProgress(),
            'created_at' => $exportJob->getCreatedAt()->format('c'),
        ];

        if ($detailed) {
            $data['width'] = $exportJob->getWidth();
            $data['height'] = $exportJob->getHeight();
            $data['quality'] = $exportJob->getQuality();
            $data['scale'] = $exportJob->getScale();
            $data['transparent'] = $exportJob->isTransparent();
            $data['background_color'] = $exportJob->getBackgroundColor();
            $data['animation_settings'] = $exportJob->getAnimationSettings();
            $data['file_size'] = $exportJob->getFileSize();
            $data['file_name'] = $exportJob->getFileName();
            $data['mime_type'] = $exportJob->getMimeType();
            $data['processing_time_ms'] = $exportJob->getProcessingTimeMs();
            $data['metadata'] = $exportJob->getMetadata();
            
            if ($exportJob->getErrorMessage()) {
                $data['error_message'] = $exportJob->getErrorMessage();
                $data['error_details'] = $exportJob->getErrorDetails();
            }
            
            if ($exportJob->getStartedAt()) {
                $data['started_at'] = $exportJob->getStartedAt()->format('c');
            }
            
            if ($exportJob->getCompletedAt()) {
                $data['completed_at'] = $exportJob->getCompletedAt()->format('c');
            }
            
            if ($exportJob->getExpiresAt()) {
                $data['expires_at'] = $exportJob->getExpiresAt()->format('c');
                $data['is_expired'] = $exportJob->isExpired();
            }
        }

        return $data;
    }
}
