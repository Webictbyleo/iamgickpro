<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\VideoAnalysis;
use App\Entity\User;
use App\Message\AnalyzeVideoMessage;
use App\Repository\VideoAnalysisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for analyzing YouTube videos and generating thumbnails using AI
 */
class VideoAnalysisService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly VideoAnalysisRepository $videoAnalysisRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly string $youtubeApiKey,
    ) {}

    /**
     * Extract video information from YouTube URL
     */
    public function extractVideoInfo(string $youtubeUrl): array
    {
        $this->validateYouTubeUrl($youtubeUrl);
        
        $videoId = $this->extractVideoId($youtubeUrl);
        
        try {
            $response = $this->httpClient->request('GET', 'https://www.googleapis.com/youtube/v3/videos', [
                'query' => [
                    'id' => $videoId,
                    'part' => 'snippet,contentDetails,statistics',
                    'key' => $this->youtubeApiKey,
                ],
            ]);

            $data = $response->toArray();
            
            if (empty($data['items'])) {
                throw new \RuntimeException('Video not found or not accessible');
            }

            $video = $data['items'][0];
            $snippet = $video['snippet'];
            $contentDetails = $video['contentDetails'];
            $statistics = $video['statistics'];

            return [
                'videoId' => $videoId,
                'title' => $snippet['title'],
                'description' => $snippet['description'] ?? '',
                'thumbnailUrl' => $this->getBestThumbnailUrl($snippet['thumbnails']),
                'duration' => $contentDetails['duration'],
                'channelTitle' => $snippet['channelTitle'],
                'publishedAt' => $snippet['publishedAt'],
                'viewCount' => (int) ($statistics['viewCount'] ?? 0),
                'likeCount' => (int) ($statistics['likeCount'] ?? 0),
                'tags' => $snippet['tags'] ?? [],
                'categoryId' => $snippet['categoryId'] ?? null,
                'defaultLanguage' => $snippet['defaultLanguage'] ?? null,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to extract YouTube video info', [
                'videoId' => $videoId,
                'url' => $youtubeUrl,
                'error' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException('Failed to extract video information: ' . $e->getMessage());
        }
    }

    /**
     * Generate design thumbnails from YouTube video
     */
    public function generateDesignFromVideo(
        string $youtubeUrl,
        User $user,
        array $options = []
    ): VideoAnalysis {
        $videoInfo = $this->extractVideoInfo($youtubeUrl);
        $videoId = $this->extractVideoId($youtubeUrl);

        // Create video analysis entity
        $analysis = new VideoAnalysis(
            user: $user,
            videoUrl: $youtubeUrl,
            videoId: $videoId,
            style: $options['style'] ?? VideoAnalysis::STYLE_PROFESSIONAL,
            size: $options['size'] ?? VideoAnalysis::SIZE_STANDARD,
            maxThumbnails: $options['maxThumbnails'] ?? 5,
            customPrompt: $options['customPrompt'] ?? null,
            designTypes: $options['designTypes'] ?? ['thumbnail']
        );

        $analysis->setVideoInfo($videoInfo);

        $this->entityManager->persist($analysis);
        $this->entityManager->flush();

        // Queue the job for background processing
        $this->queueVideoAnalysis($analysis);

        return $analysis;
    }


    /**
     * Update analysis progress
     */
    public function updateProgress(VideoAnalysis $analysis, int $progress, ?string $statusMessage = null): void
    {
        $analysis->setProgress($progress);
        
        $this->entityManager->flush();
        
        $this->logger->debug('Video analysis progress updated', [
            'analysisId' => $analysis->getId(),
            'progress' => $progress,
            'statusMessage' => $statusMessage,
        ]);
    }

    /**
     * Mark analysis as completed
     */
    public function markCompleted(VideoAnalysis $analysis, array $results): void
    {
        $analysis->setStatus(VideoAnalysis::STATUS_COMPLETED);
        $analysis->setProgress(100);
        
        // Store results in appropriate fields
        if (isset($results['suggestedDesigns'])) {
            $analysis->setSuggestedDesigns($results['suggestedDesigns']);
        }
        if (isset($results['colorPalette'])) {
            $analysis->setColorPalette($results['colorPalette']);
        }
        if (isset($results['dominantThemes'])) {
            $analysis->setDominantThemes($results['dominantThemes']);
        }
        if (isset($results['keyMoments'])) {
            $analysis->setKeyMoments($results['keyMoments']);
        }
        if (isset($results['transcript'])) {
            $analysis->setTranscript($results['transcript']);
        }
        if (isset($results['extractedFrames'])) {
            $analysis->setExtractedFrames($results['extractedFrames']);
        }
        
        $this->entityManager->flush();
        
        $this->logger->info('Video analysis completed', [
            'analysisId' => $analysis->getId(),
            'resultsCount' => count($results['suggestedDesigns'] ?? []),
        ]);
    }

    /**
     * Mark analysis as failed
     */
    public function markFailed(VideoAnalysis $analysis, string $errorMessage): void
    {
        $analysis->setStatus(VideoAnalysis::STATUS_FAILED);
        $analysis->setErrorMessage($errorMessage);
        
        $this->entityManager->flush();
        
        $this->logger->error('Video analysis failed', [
            'analysisId' => $analysis->getId(),
            'error' => $errorMessage,
        ]);
    }

    /**
     * Queue video analysis for background processing
     */
    private function queueVideoAnalysis(VideoAnalysis $analysis): void
    {
        $analysis->setStatus(VideoAnalysis::STATUS_PENDING);
        $this->entityManager->flush();
        
        // Dispatch message to message bus for background processing
        $message = new AnalyzeVideoMessage(
            $analysis->getId(),
            $analysis->getVideoUrl(),
            [
                'style' => $analysis->getStyle(),
                'size' => $analysis->getSize(),
                'maxThumbnails' => $analysis->getMaxThumbnails(),
                'customPrompt' => $analysis->getCustomPrompt(),
                'designTypes' => $analysis->getDesignTypes()
            ]
        );
        
        $this->messageBus->dispatch($message);
        
        $this->logger->info('Video analysis queued for processing', [
            'analysisId' => $analysis->getId(),
            'videoId' => $analysis->getVideoId(),
        ]);
    }

    /**
     * Get the best available thumbnail URL
     */
    private function getBestThumbnailUrl(array $thumbnails): string
    {
        // Prefer maxresdefault, then high, then medium, then default
        $priorities = ['maxresdefault', 'high', 'medium', 'default'];
        
        foreach ($priorities as $priority) {
            if (isset($thumbnails[$priority]['url'])) {
                return $thumbnails[$priority]['url'];
            }
        }
        
        // Fallback if no thumbnails found
        return '';
    }

    /**
     * Validate YouTube URL format
     */
    private function validateYouTubeUrl(string $url): void
    {
        $pattern = '/^https?:\/\/(?:www\.)?(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})(?:\S+)?$/';
        
        if (!preg_match($pattern, $url)) {
            throw new \InvalidArgumentException('Invalid YouTube URL format');
        }
    }

    /**
     * Extract video ID from YouTube URL
     */
    private function extractVideoId(string $url): string
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        throw new \InvalidArgumentException('Could not extract video ID from URL');
    }

    /**
     * Get paginated list of user's video analysis jobs
     */
    public function getUserJobs(
        User $user, 
        int $page = 1, 
        int $limit = 10, 
        ?string $status = null, 
        string $sort = 'newest'
    ): array {
        // Validate pagination parameters
        $page = max(1, $page);
        $limit = min(50, max(1, $limit));
        
        $criteria = ['user' => $user];
        if ($status !== null) {
            $criteria['status'] = $status;
        }
        
        // Determine sort order
        $orderBy = match ($sort) {
            'oldest' => ['createdAt' => 'ASC'],
            'status' => ['status' => 'ASC', 'createdAt' => 'DESC'],
            default => ['createdAt' => 'DESC'] // 'newest'
        };
        
        $totalJobs = $this->videoAnalysisRepository->count($criteria);
        $jobs = $this->videoAnalysisRepository->findBy(
            $criteria,
            $orderBy,
            $limit,
            ($page - 1) * $limit
        );
        
        $totalPages = (int) ceil($totalJobs / $limit);
        
        return [
            'jobs' => $jobs,
            'pagination' => [
                'total' => $totalJobs,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => $totalPages,
                'hasNext' => $page < $totalPages,
                'hasPrevious' => $page > 1
            ]
        ];
    }

    /**
     * Get a specific video analysis job for a user
     */
    public function getAnalysisJob(string $jobId, User $user): ?VideoAnalysis
    {
        return $this->videoAnalysisRepository->findOneBy([
            'uuid' => $jobId,
            'user' => $user
        ]);
    }

    /**
     * Delete a video analysis job
     */
    public function deleteAnalysisJob(VideoAnalysis $analysis, User $user): void
    {
        // Verify ownership
        if ($analysis->getUser() !== $user) {
            throw new \InvalidArgumentException('You do not have permission to delete this job');
        }

        // Check if job can be deleted
        if ($analysis->getStatus() === VideoAnalysis::STATUS_PROCESSING) {
            throw new \InvalidArgumentException('Cannot delete a job that is currently processing');
        }

        try {
            $this->entityManager->remove($analysis);
            $this->entityManager->flush();

            $this->logger->info('Video analysis job deleted', [
                'job_id' => $analysis->getId(),
                'user_id' => $user->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete video analysis job', [
                'job_id' => $analysis->getId(),
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to delete video analysis job');
        }
    }

    /**
     * Retry a failed video analysis job
     */
    public function retryAnalysisJob(VideoAnalysis $analysis, User $user): VideoAnalysis
    {
        // Verify ownership
        if ($analysis->getUser() !== $user) {
            throw new \InvalidArgumentException('You do not have permission to retry this job');
        }

        // Check if job can be retried
        if ($analysis->getStatus() !== VideoAnalysis::STATUS_FAILED) {
            throw new \InvalidArgumentException('Only failed jobs can be retried');
        }

        try {
            // Reset job status and clear error info
            $analysis->setStatus(VideoAnalysis::STATUS_PENDING);
            $analysis->setProgress(0);
            $analysis->setErrorMessage(null);

            $this->entityManager->flush();

            // Queue the job for processing
            $this->queueVideoAnalysis($analysis);

            $this->logger->info('Video analysis job retry initiated', [
                'job_id' => $analysis->getId(),
                'user_id' => $user->getId(),
                'video_url' => $analysis->getVideoUrl()
            ]);

            return $analysis;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retry video analysis job', [
                'job_id' => $analysis->getId(),
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to retry video analysis job');
        }
    }
}
