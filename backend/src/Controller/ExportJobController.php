<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\TypedResponseTrait;
use App\DTO\Request\CreateExportJobRequestDTO;
use App\DTO\Response\ErrorResponseDTO;
use App\DTO\Response\ExportJobResponseDTO;
use App\DTO\Response\SuccessResponseDTO;
use App\Entity\ExportJob;
use App\Repository\ExportJobRepository;
use App\Service\ResponseDTOFactory;
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

/**
 * Export Job Controller
 * 
 * Manages export job operations for design rendering and file generation.
 * Handles job creation, monitoring, cancellation, retry, and file download functionality.
 * All endpoints require authentication and enforce user ownership for security.
 */
#[Route('/api/export-jobs', name: 'api_export_jobs_')]
class ExportJobController extends AbstractController
{
    use TypedResponseTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ExportJobRepository $exportJobRepository,
        private readonly ValidatorInterface $validator,
        private readonly ResponseDTOFactory $responseDTOFactory,
    ) {}

    /**
     * List export jobs for authenticated user
     * 
     * Returns a paginated list of export jobs belonging to the authenticated user.
     * Supports filtering by status and format, with configurable pagination.
     * 
     * @param Request $request HTTP request with optional query parameters:
     *                        - page: Page number (default: 1, min: 1)
     *                        - limit: Items per page (default: 20, max: 50)
     *                        - status: Filter by job status (pending, processing, completed, failed, cancelled)
     *                        - format: Filter by export format (png, jpg, svg, mp4, gif)
     * @return JsonResponse<ExportJobResponseDTO|ErrorResponseDTO> Paginated list of export jobs or error response
     */
    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(Request $request): JsonResponse
    {
        try {
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

            return $this->exportJobResponse(
                $this->responseDTOFactory->createExportJobListResponse($jobs, $total, $page, $limit)
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to retrieve export jobs'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get details of a specific export job
     * 
     * Returns detailed information about a single export job.
     * Only allows access to export jobs owned by the authenticated user.
     * 
     * @param ExportJob $exportJob The export job entity (auto-resolved by Symfony)
     * @return JsonResponse<ExportJobResponseDTO|ErrorResponseDTO> Export job details or error response
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(ExportJob $exportJob): JsonResponse
    {
        try {
            // Only allow access to own export jobs
            if ($exportJob->getUser() !== $this->getUser()) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Access denied'),
                    Response::HTTP_FORBIDDEN
                );
            }

            return $this->exportJobResponse(
                $this->responseDTOFactory->createExportJobResponse($exportJob)
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to retrieve export job'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Create a new export job
     * 
     * Creates a new export job for rendering a design in the specified format and settings.
     * Validates user access to the design and enqueues the job for background processing.
     * 
     * @param CreateExportJobRequestDTO $dto Export job configuration including:
     *                                      - designId: ID of the design to export
     *                                      - format: Output format (png, jpg, svg, mp4, gif)
     *                                      - quality: Export quality (1-100)
     *                                      - width: Output width in pixels
     *                                      - height: Output height in pixels
     *                                      - scale: Scale factor for the export
     *                                      - transparent: Whether to use transparent background
     *                                      - backgroundColor: Background color (if not transparent)
     *                                      - animationSettings: Animation configuration for video formats
     * @return JsonResponse<ExportJobResponseDTO|ErrorResponseDTO> Created export job details or error response
     */
    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(CreateExportJobRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();

            // Get the design entity
            $design = $this->entityManager->getRepository(\App\Entity\Design::class)->find($dto->designId);
            if (!$design) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Design not found'),
                    Response::HTTP_NOT_FOUND
                );
            }

            // Check if user owns the design or has access to it
            if ($design->getUser() !== $user && !$design->isPublic()) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Access denied'),
                    Response::HTTP_FORBIDDEN
                );
            }

            $exportJob = new ExportJob(
                $user,
                $design,
                $dto->format,
                $dto->quality,
                $dto->width,
                $dto->height,
                $dto->scale,
                $dto->transparent,
                $dto->backgroundColor,
                $dto->animationSettings
            );

            $errors = $this->validator->validate($exportJob);
            if (count($errors) > 0) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse(
                        'Validation failed',
                        ['errors' => (string) $errors]
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $this->entityManager->persist($exportJob);
            $this->entityManager->flush();

            // TODO: Dispatch to message queue for async processing
            // $this->messageBus->dispatch(new ProcessExportJobMessage($exportJob->getId()));

            return $this->exportJobResponse(
                $this->responseDTOFactory->createExportJobResponse($exportJob),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to create export job'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Update an export job (Not allowed)
     * 
     * Export jobs are immutable after creation and cannot be modified.
     * This endpoint always returns an error indicating that modifications are not permitted.
     * 
     * @param ExportJob $exportJob The export job entity (auto-resolved by Symfony)
     * @return JsonResponse<ErrorResponseDTO> Error response indicating operation not allowed
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(ExportJob $exportJob): JsonResponse
    {
        try {
            // Only allow access to own export jobs
            if ($exportJob->getUser() !== $this->getUser()) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Access denied'),
                    Response::HTTP_FORBIDDEN
                );
            }

            // Export job properties are readonly after creation
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Export job cannot be modified after creation'),
                Response::HTTP_BAD_REQUEST
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to update export job'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Delete an export job
     * 
     * Deletes an export job and its associated output file.
     * Only allows deletion of jobs in pending, failed, or completed status.
     * Only allows access to export jobs owned by the authenticated user.
     * 
     * @param ExportJob $exportJob The export job entity (auto-resolved by Symfony)
     * @return JsonResponse<SuccessResponseDTO|ErrorResponseDTO> Success confirmation or error response
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(ExportJob $exportJob): JsonResponse
    {
        try {
            // Only allow access to own export jobs
            if ($exportJob->getUser() !== $this->getUser()) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Access denied'),
                    Response::HTTP_FORBIDDEN
                );
            }

            // Only allow deletion of pending or failed jobs
            if (!in_array($exportJob->getStatus(), ['pending', 'failed', 'completed'])) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Cannot delete job that is in progress'),
                    Response::HTTP_BAD_REQUEST
                );
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

            return $this->successResponse(
                $this->responseDTOFactory->createSuccessResponse('Export job deleted successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to delete export job'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Cancel a pending or processing export job
     * 
     * Cancels an export job that is currently pending or being processed.
     * Sets the job status to cancelled and stops any ongoing processing.
     * Only allows access to export jobs owned by the authenticated user.
     * 
     * @param ExportJob $exportJob The export job entity (auto-resolved by Symfony)
     * @return JsonResponse<ExportJobResponseDTO|ErrorResponseDTO> Updated export job details or error response
     */
    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(ExportJob $exportJob): JsonResponse
    {
        try {
            // Only allow access to own export jobs
            if ($exportJob->getUser() !== $this->getUser()) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Access denied'),
                    Response::HTTP_FORBIDDEN
                );
            }

            // Only allow cancellation of pending or processing jobs
            if (!in_array($exportJob->getStatus(), ['pending', 'processing'])) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Cannot cancel job that is not in progress'),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $exportJob->cancel();
            $this->entityManager->flush();

            return $this->exportJobResponse(
                $this->responseDTOFactory->createExportJobResponse($exportJob)
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to cancel export job'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Retry a failed export job
     * 
     * Resets a failed export job back to pending status for re-processing.
     * Clears error messages and resets progress to zero.
     * Only allows retry of jobs with failed status.
     * Only allows access to export jobs owned by the authenticated user.
     * 
     * @param ExportJob $exportJob The export job entity (auto-resolved by Symfony)
     * @return JsonResponse<ExportJobResponseDTO|ErrorResponseDTO> Updated export job details or error response
     */
    #[Route('/{id}/retry', name: 'retry', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function retry(ExportJob $exportJob): JsonResponse
    {
        try {
            // Only allow access to own export jobs
            if ($exportJob->getUser() !== $this->getUser()) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Access denied'),
                    Response::HTTP_FORBIDDEN
                );
            }

            // Only allow retry for failed jobs
            if (!$exportJob->isFailed()) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Can only retry failed jobs'),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $exportJob->setStatus('pending');
            $exportJob->setProgress(0);
            $exportJob->setErrorMessage(null);
            $this->entityManager->flush();

            // TODO: Dispatch to message queue for async processing
            // $this->messageBus->dispatch(new ProcessExportJobMessage($exportJob->getId()));

            return $this->exportJobResponse(
                $this->responseDTOFactory->createExportJobResponse($exportJob)
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to retry export job'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Download export job output file
     * 
     * Downloads the generated file from a completed export job.
     * Returns the file as an attachment with appropriate headers.
     * Only allows download of completed jobs with existing output files.
     * Only allows access to export jobs owned by the authenticated user.
     * 
     * @param ExportJob $exportJob The export job entity (auto-resolved by Symfony)
     * @return Response File download response with proper headers or error response
     */
    #[Route('/{id}/download', name: 'download', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function download(ExportJob $exportJob): Response
    {
        try {
            // Only allow access to own export jobs
            if ($exportJob->getUser() !== $this->getUser()) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Access denied'),
                    Response::HTTP_FORBIDDEN
                );
            }

            // Only allow download of completed jobs
            if ($exportJob->getStatus() !== 'completed') {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Export job is not completed'),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $filePath = $exportJob->getFilePath();
            if (!$filePath || !file_exists($filePath)) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Export file not found'),
                    Response::HTTP_NOT_FOUND
                );
            }

            $filename = $exportJob->getFileName() ?? basename($filePath);
            $mimeType = $exportJob->getMimeType() ?? mime_content_type($filePath);

            $response = $this->file($filePath, $filename, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
            $response->headers->set('Content-Type', $mimeType);
            
            return $response;
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to download export file'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get export job statistics for authenticated user
     * 
     * Returns comprehensive statistics about the user's export job usage,
     * including totals by status, format breakdown, and success rate.
     * 
     * @return JsonResponse<array|ErrorResponseDTO> Export job statistics or error response
     */
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->exportJobRepository->getUserStats($this->getUser());

            $response = [
                'total_jobs' => $stats['total'],
                'completed_jobs' => $stats['completed'],
                'failed_jobs' => $stats['failed'],
                'pending_jobs' => $stats['pending'],
                'processing_jobs' => $stats['processing'],
                'format_breakdown' => $stats['by_format'],
                'success_rate' => $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100, 2) : 0,
            ];

            return new JsonResponse($response);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to retrieve export job statistics'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get export job queue status (Admin only)
     * 
     * Returns system-wide export job queue statistics and health information.
     * Includes pending/processing job counts, average processing times, and queue health metrics.
     * Only accessible to users with ROLE_ADMIN.
     * 
     * @return JsonResponse<array|ErrorResponseDTO> Queue status information or error response
     */
    #[Route('/queue-status', name: 'queue_status', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function queueStatus(): JsonResponse
    {
        try {
            $queueStats = $this->exportJobRepository->getQueueStats();

            $response = [
                'pending_jobs' => $queueStats['pending'],
                'processing_jobs' => $queueStats['processing'],
                'failed_jobs' => $queueStats['failed'],
                'avg_processing_time' => $queueStats['avg_processing_time'],
                'queue_health' => $queueStats['queue_health'],
            ];

            return new JsonResponse($response);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to retrieve queue status'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
