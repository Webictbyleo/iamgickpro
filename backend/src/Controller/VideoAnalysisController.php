<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\TypedResponseTrait;
use App\DTO\Request\ExtractVideoInfoRequestDTO;
use App\DTO\Request\GenerateDesignFromVideoRequestDTO;
use App\DTO\Request\VideoAnalysisJobListRequestDTO;
use App\Entity\User;
use App\Entity\VideoAnalysis;
use App\Service\ResponseDTOFactory;
use App\Service\VideoAnalysisService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

/**
 * Video Analysis Controller
 * 
 * Provides YouTube video analysis and AI-powered thumbnail generation functionality.
 * Handles video metadata extraction, background processing of analysis jobs,
 * and thumbnail design generation using artificial intelligence.
 * 
 * Features:
 * - YouTube video information extraction and metadata parsing
 * - AI-powered thumbnail design generation from video content
 * - Background job processing with status tracking and progress updates
 * - Multiple thumbnail styles and customization options
 * - Job queue management with retry mechanisms
 * - User permission-based access control and job ownership
 * 
 * All endpoints require user authentication and implement proper error handling.
 * Processing jobs are queued for background execution with real-time status updates.
 */
#[Route('/api/video-analysis')]
#[IsGranted('ROLE_USER')]
class VideoAnalysisController extends AbstractController
{
    use TypedResponseTrait;

    public function __construct(
        private readonly VideoAnalysisService $videoAnalysisService,
        private readonly ResponseDTOFactory $responseDTOFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Generate designs from YouTube video
     * 
     * Creates a new video analysis job to process a YouTube video and generate
     * AI-powered thumbnail designs. The job is queued for background processing
     * and returns immediately with a job ID for status tracking.
     * 
     * Request Body:
     * - videoUrl: YouTube video URL (required)
     * - designTypes: Array of design types to generate (optional)
     * - customPrompt: Custom AI prompt for design generation (optional)
     * - options: Thumbnail generation options including style, size, count (optional)
     * 
     * @param GenerateDesignFromVideoRequestDTO $request The video analysis request
     * @return JsonResponse Video analysis job response with job ID and initial status
     */
    #[Route('/generate', name: 'video_analysis_generate', methods: ['POST'])]
    public function generateDesignsFromVideo(
        #[MapRequestPayload] GenerateDesignFromVideoRequestDTO $request
    ): JsonResponse {
        try {
            /** @var User $user */
            $user = $this->getUser();

            $this->logger->info('Video analysis generation requested', [
                'user_id' => $user->getId(),
                'video_url' => $request->videoUrl,
                'style' => $request->getStyle(),
                'max_thumbnails' => $request->getMaxThumbnails()
            ]);

            // Create video analysis job
            $analysis = $this->videoAnalysisService->generateDesignFromVideo(
                $request->videoUrl,
                $user,
                [
                    'style' => $request->getStyle(),
                    'maxThumbnails' => $request->getMaxThumbnails(),
                    'customPrompt' => $request->getEffectiveCustomPrompt(),
                    'designTypes' => $request->getDesignTypes()
                ]
            );

            $response = $this->responseDTOFactory->createVideoAnalysisJobResponse(
                $analysis,
                'Video analysis job created successfully'
            );

            return $this->videoAnalysisResponse($response, Response::HTTP_CREATED);

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Invalid video analysis request', [
                'error' => $e->getMessage(),
                'video_url' => $request->videoUrl ?? 'N/A'
            ]);

            $response = $this->responseDTOFactory->createErrorResponse(
                $e->getMessage(),
                ['field' => 'videoUrl']
            );

            return $this->errorResponse($response, Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            $this->logger->error('Video analysis generation failed', [
                'error' => $e->getMessage(),
                'video_url' => $request->videoUrl ?? 'N/A'
            ]);

            $response = $this->responseDTOFactory->createErrorResponse(
                'Failed to create video analysis job. Please try again.'
            );

            return $this->errorResponse($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get video analysis job status
     * 
     * Retrieves the current status and results of a video analysis job.
     * Returns job details including progress, status, and results if completed.
     * Users can only access their own jobs.
     * 
     * @param string $jobId The UUID of the video analysis job
     * @return JsonResponse Video analysis job response with current status and results
     */
    #[Route('/jobs/{jobId}', name: 'video_analysis_job_status', methods: ['GET'])]
    public function getAnalysisJob(string $jobId): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            $analysis = $this->videoAnalysisService->getAnalysisJob($jobId, $user);

            if (!$analysis) {
                $response = $this->responseDTOFactory->createErrorResponse(
                    'Video analysis job not found'
                );

                return $this->errorResponse($response, Response::HTTP_NOT_FOUND);
            }

            $response = $this->responseDTOFactory->createVideoAnalysisJobResponse(
                $analysis,
                'Video analysis job retrieved successfully'
            );

            return $this->videoAnalysisResponse($response);

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve video analysis job', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            $response = $this->responseDTOFactory->createErrorResponse(
                'Failed to retrieve video analysis job'
            );

            return $this->errorResponse($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get user's video analysis jobs
     * 
     * Retrieves a paginated list of video analysis jobs for the authenticated user.
     * Supports filtering by status and sorting options for job management.
     * 
     * Query Parameters:
     * - page: Page number for pagination (default: 1)
     * - limit: Items per page, max 50 (default: 10)
     * - status: Filter by job status (optional)
     * - sort: Sort order - newest, oldest, status (default: newest)
     * 
     * @param Request $request HTTP request with query parameters
     * @return JsonResponse Paginated list of video analysis jobs
     */
    #[Route('/jobs', name: 'video_analysis_jobs', methods: ['GET'])]
    public function getAnalysisJobs(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            // Create request DTO from query parameters
            $requestDTO = new VideoAnalysisJobListRequestDTO(
                page: (int) $request->query->get('page', 1),
                limit: (int) $request->query->get('limit', 10),
                status: $request->query->get('status'),
                sort: $request->query->get('sort', 'newest')
            );

            $result = $this->videoAnalysisService->getUserJobs(
                user: $user,
                page: $requestDTO->page,
                limit: $requestDTO->limit,
                status: $requestDTO->status,
                sort: $requestDTO->sort
            );

            $response = $this->responseDTOFactory->createVideoAnalysisListResponse(
                jobs: $result['jobs'],
                total: $result['pagination']['total'],
                page: $result['pagination']['page'],
                totalPages: $result['pagination']['totalPages'],
                message: 'Video analysis jobs retrieved successfully'
            );

            return $this->videoAnalysisResponse($response);

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve video analysis jobs', [
                'error' => $e->getMessage()
            ]);

            $response = $this->responseDTOFactory->createErrorResponse(
                'Failed to retrieve video analysis jobs'
            );

            return $this->errorResponse($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete video analysis job
     * 
     * Deletes a video analysis job and its associated data.
     * Users can only delete their own jobs. Jobs that are currently
     * processing cannot be deleted and must be cancelled first.
     * 
     * @param string $jobId The UUID of the video analysis job to delete
     * @return JsonResponse Success response confirming deletion
     */
    #[Route('/jobs/{jobId}', name: 'video_analysis_job_delete', methods: ['DELETE'])]
    public function deleteAnalysisJob(string $jobId): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            $analysis = $this->videoAnalysisService->getAnalysisJob($jobId, $user);

            if (!$analysis) {
                $response = $this->responseDTOFactory->createErrorResponse(
                    'Video analysis job not found'
                );

                return $this->errorResponse($response, Response::HTTP_NOT_FOUND);
            }

            if ($analysis->getStatus() === VideoAnalysis::STATUS_PROCESSING) {
                $response = $this->responseDTOFactory->createErrorResponse(
                    'Cannot delete a job that is currently processing'
                );

                return $this->errorResponse($response, Response::HTTP_CONFLICT);
            }

            $this->videoAnalysisService->deleteAnalysisJob($analysis, $user);

            $response = $this->responseDTOFactory->createSuccessResponse(
                'Video analysis job deleted successfully'
            );

            return $this->successResponse($response);

        } catch (\InvalidArgumentException $e) {
            $response = $this->responseDTOFactory->createErrorResponse(
                $e->getMessage()
            );

            return $this->errorResponse($response, Response::HTTP_FORBIDDEN);

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete video analysis job', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            $response = $this->responseDTOFactory->createErrorResponse(
                'Failed to delete video analysis job'
            );

            return $this->errorResponse($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Retry failed video analysis job
     * 
     * Retries a failed video analysis job by resetting its status and
     * requeuing it for processing. Only failed jobs can be retried.
     * 
     * @param string $jobId The UUID of the video analysis job to retry
     * @return JsonResponse Updated video analysis job response
     */
    #[Route('/jobs/{jobId}/retry', name: 'video_analysis_job_retry', methods: ['POST'])]
    public function retryAnalysisJob(string $jobId): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            $analysis = $this->videoAnalysisService->getAnalysisJob($jobId, $user);

            if (!$analysis) {
                $response = $this->responseDTOFactory->createErrorResponse(
                    'Video analysis job not found'
                );

                return $this->errorResponse($response, Response::HTTP_NOT_FOUND);
            }

            $updatedAnalysis = $this->videoAnalysisService->retryAnalysisJob($analysis, $user);

            $response = $this->responseDTOFactory->createVideoAnalysisJobResponse(
                $updatedAnalysis,
                'Video analysis job retry initiated successfully'
            );

            return $this->videoAnalysisResponse($response);

        } catch (\InvalidArgumentException $e) {
            $response = $this->responseDTOFactory->createErrorResponse(
                $e->getMessage()
            );

            return $this->errorResponse($response, Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            $this->logger->error('Failed to retry video analysis job', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            $response = $this->responseDTOFactory->createErrorResponse(
                'Failed to retry video analysis job'
            );

            return $this->errorResponse($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Extract basic video information
     * 
     * Extracts basic metadata from a YouTube video without creating a full
     * analysis job. Returns video title, description, duration, thumbnail,
     * and other metadata for preview purposes.
     * 
     * Query Parameters:
     * - videoUrl: YouTube video URL (required)
     * 
     * @param Request $request HTTP request with video URL parameter
     * @return JsonResponse Video information response
     */
    #[Route('/extract-info', name: 'video_analysis_extract_info', methods: ['GET'])]
    public function extractVideoInfo(Request $request): JsonResponse
    {
        try {
            $videoUrl = $request->query->get('videoUrl');

            if (!$videoUrl) {
                $response = $this->responseDTOFactory->createErrorResponse(
                    'Video URL is required',
                    ['field' => 'videoUrl']
                );

                return $this->errorResponse($response, Response::HTTP_BAD_REQUEST);
            }

            // Create request DTO for validation
            $requestDTO = new ExtractVideoInfoRequestDTO($videoUrl);

            $videoInfo = $this->videoAnalysisService->extractVideoInfo($requestDTO->videoUrl);

            $response = $this->responseDTOFactory->createVideoInfoResponse(
                $videoInfo,
                'Video information extracted successfully'
            );

            return $this->videoAnalysisResponse($response);

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Invalid video URL for info extraction', [
                'video_url' => $request->query->get('videoUrl'),
                'error' => $e->getMessage()
            ]);

            $response = $this->responseDTOFactory->createErrorResponse(
                $e->getMessage(),
                ['field' => 'videoUrl']
            );

            return $this->errorResponse($response, Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            $this->logger->error('Video info extraction failed', [
                'video_url' => $request->query->get('videoUrl'),
                'error' => $e->getMessage()
            ]);

            $response = $this->responseDTOFactory->createErrorResponse(
                'Failed to extract video information. Please try again.'
            );

            return $this->errorResponse($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
