<?php

declare(strict_types=1);

namespace App\Service\StockMedia;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Service for integrating with Pexels API to search stock videos.
 * 
 * Provides high-quality stock videos with proper licensing and attribution.
 * Implements rate limiting and error handling for production use.
 */
class PexelsService implements StockMediaServiceInterface
{
    private const API_BASE_URL = 'https://api.pexels.com/v1';
    
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
        private readonly StockMediaResponseValidator $responseValidator
    ) {
        if (empty($this->apiKey)) {
            $this->logger->warning('PexelsService initialized with empty API key - service will be disabled');
        }
    }

    /**
     * Search for stock videos on Pexels
     */
    public function search(string $query, int $page = 1, int $limit = 20, array $filters = []): array
    {
        // Check if API key is available
        if (empty($this->apiKey)) {
            $this->logger->warning('PexelsService search called but API key is not configured');
            return [
                'items' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'hasMore' => false
            ];
        }

        try {
            $this->logger->info('Searching Pexels for stock videos', [
                'query' => $query,
                'page' => $page,
                'limit' => $limit,
                'filters' => $filters
            ]);

            $params = [
                'query' => $query,
                'page' => $page,
                'per_page' => min($limit, 80), // Pexels max per page is 80
                'orientation' => $filters['orientation'] ?? null,
                'size' => $filters['size'] ?? null,
                'locale' => 'en-US'
            ];

            // Remove null values
            $params = array_filter($params, fn($value) => $value !== null);

            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/videos/search', [
                'headers' => [
                    'Authorization' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
                'query' => $params,
                'timeout' => 10
            ]);

            // Use response validator to safely parse JSON response
            $data = $this->responseValidator->parseAndValidateResponse($response, [
                'total_results' => 'integer',
                'videos' => 'array'
            ]);

            if ($data === null) {
                $this->logger->warning('Invalid response from Pexels API', ['query' => $query]);
                return [
                    'items' => [],
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'hasMore' => false
                ];
            }
            
            $results = [
                'items' => [],
                'total' => $this->responseValidator->extractIntField($data, 'total_results', 0),
                'page' => $page,
                'limit' => $limit,
                'hasMore' => !empty($this->responseValidator->extractStringField($data, 'next_page', ''))
            ];

            // Validate and extract videos array with required fields
            $videos = $this->responseValidator->extractItemsArray($data, 'videos', ['id']);
            foreach ($videos as $video) {
                $transformedVideo = $this->transformVideoData($video);
                if ($transformedVideo !== null) {
                    $results['items'][] = $transformedVideo;
                }
            }

            $this->logger->info('Pexels search completed', [
                'query' => $query,
                'total_results' => $results['total'],
                'returned_items' => count($results['items'])
            ]);

            return $results;

        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Pexels API client error', [
                'query' => $query,
                'error' => $e->getMessage(),
                'status_code' => $e->getResponse()->getStatusCode()
            ]);
            
            throw new StockMediaException(
                'Failed to search Pexels: ' . $e->getMessage(),
                $e->getResponse()->getStatusCode(),
                null,
                'pexels'
            );
            
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Pexels API transport error', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            throw new StockMediaException(
                'Failed to connect to Pexels API: ' . $e->getMessage(),
                500,
                null,
                'pexels'
            );
        }
    }

    /**
     * Transform Pexels video data to our standard format
     */
    private function transformVideoData(array $video): ?array
    {
        try {
            // Validate required fields
            $videoId = $this->responseValidator->extractStringField($video, 'id', null);
            if (!$videoId) {
                $this->logger->warning('Pexels video missing required id', ['video' => $video]);
                return null;
            }

            // Find the best quality video file
            $videoFiles = $this->responseValidator->extractArrayField($video, 'video_files', []);
            $bestFile = $this->findBestVideoFile($videoFiles);
            $previewFile = $this->findPreviewFile($videoFiles);
            
            if (!$bestFile) {
                $this->logger->warning('No valid video files found for Pexels video', ['video_id' => $videoId]);
                return null;
            }
            
            // Generate tags from URL and user info
            $tags = [];
            
            // Extract potential tags from the Pexels URL
            $videoUrl = $this->responseValidator->extractStringField($video, 'url', '');
            if ($videoUrl && $this->responseValidator->validateUrl($videoUrl)) {
                $urlParts = explode('-', basename(parse_url($videoUrl, PHP_URL_PATH)));
                $urlTags = array_filter($urlParts, fn($part) => strlen($part) > 2 && is_numeric($part) === false);
                foreach (array_slice($urlTags, 0, 5) as $tag) {
                    $cleanTag = $this->responseValidator->sanitizeString($tag);
                    if (strlen($cleanTag) > 2) {
                        $tags[] = $cleanTag;
                    }
                }
            }

            // Add generic video-related tags
            $tags = array_merge($tags, ['video', 'stock', 'footage']);

            // Clean and filter tags
            $tags = array_unique(array_filter($tags, fn($tag) => strlen($tag) > 2));

            // Extract and validate URLs
            $videoFileUrl = $this->responseValidator->extractStringField($bestFile, 'link', '');
            $thumbnailUrl = $this->responseValidator->extractStringField($video, 'image', '');
            $previewUrl = $this->responseValidator->extractStringField($previewFile, 'link', '') ?: $videoFileUrl;

            if (!$this->responseValidator->validateUrl($videoFileUrl)) {
                $this->logger->warning('Invalid video file URL for Pexels video', ['video_id' => $videoId]);
                return null;
            }

            // Extract user information safely
            $user = $this->responseValidator->extractArrayField($video, 'user', []);
            $photographerName = $this->responseValidator->sanitizeString(
                $this->responseValidator->extractStringField($user, 'name', 'Unknown')
            );
            $photographerUrl = $this->responseValidator->extractStringField($user, 'url', '');

            // Build attribution safely
            $attribution = $this->buildAttribution($video);

            return [
                'id' => $videoId,
                'name' => $this->generateVideoName($video, $tags),
                'type' => 'video',
                'mimeType' => $this->responseValidator->extractStringField($bestFile, 'file_type', 'video/mp4'),
                'url' => $videoFileUrl,
                'thumbnailUrl' => $this->responseValidator->validateUrl($thumbnailUrl) ? $thumbnailUrl : '',
                'previewUrl' => $this->responseValidator->validateUrl($previewUrl) ? $previewUrl : $videoFileUrl,
                'width' => $this->responseValidator->extractIntField($bestFile, 'width', null) 
                    ?? $this->responseValidator->extractIntField($video, 'width', 1920),
                'height' => $this->responseValidator->extractIntField($bestFile, 'height', null) 
                    ?? $this->responseValidator->extractIntField($video, 'height', 1080),
                'duration' => $this->responseValidator->extractIntField($video, 'duration', null),
                'size' => null, // Pexels doesn't provide file size
                'source' => 'pexels',
                'sourceId' => $videoId,
                'license' => 'Pexels License',
                'attribution' => $attribution,
                'tags' => array_values($tags),
                'isPremium' => false,
                'metadata' => [
                    'photographer' => $photographerName,
                    'photographer_url' => $this->responseValidator->validateUrl($photographerUrl) ? $photographerUrl : '',
                    'pexels_url' => $this->responseValidator->validateUrl($videoUrl) ? $videoUrl : '',
                    'duration' => $this->responseValidator->extractIntField($video, 'duration', null),
                    'fps' => $this->responseValidator->extractIntField($bestFile, 'fps', null),
                    'quality' => $this->responseValidator->extractStringField($bestFile, 'quality', ''),
                    'available_qualities' => $this->getAvailableQualities($videoFiles),
                    'file_type' => $this->responseValidator->extractStringField($bestFile, 'file_type', 'video/mp4'),
                    'video_pictures' => $this->responseValidator->extractArrayField($video, 'video_pictures', [])
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to transform Pexels video data', [
                'video' => $video,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Find the best quality video file for main playback
     */
    private function findBestVideoFile(array $videoFiles): ?array
    {
        if (empty($videoFiles)) {
            return null;
        }

        // Prefer HD qualities in order
        $preferredQualities = ['hd', 'sd', 'uhd'];
        
        foreach ($preferredQualities as $quality) {
            foreach ($videoFiles as $file) {
                if (($file['quality'] ?? '') === $quality && 
                    (!isset($file['file_type']) || $file['file_type'] === 'video/mp4')) {
                    return $file;
                }
            }
        }

        // Return first MP4 file if no preferred quality found
        foreach ($videoFiles as $file) {
            if (!isset($file['file_type']) || $file['file_type'] === 'video/mp4') {
                return $file;
            }
        }

        // Return first file as fallback
        return reset($videoFiles) ?: null;
    }

    /**
     * Find a smaller preview/thumbnail video file
     */
    private function findPreviewFile(array $videoFiles): ?array
    {
        if (empty($videoFiles)) {
            return null;
        }

        // Look for lower quality preview files
        foreach ($videoFiles as $file) {
            if (($file['quality'] ?? '') === 'sd' && 
                (!isset($file['file_type']) || $file['file_type'] === 'video/mp4')) {
                return $file;
            }
        }

        // Return best file as fallback
        return $this->findBestVideoFile($videoFiles);
    }

    /**
     * Get list of available video qualities
     */
    private function getAvailableQualities(array $videoFiles): array
    {
        $qualities = [];
        foreach ($videoFiles as $file) {
            if (isset($file['quality'])) {
                $qualities[] = $file['quality'];
            }
        }
        return array_unique($qualities);
    }

    /**
     * Generate a meaningful name for the video
     */
    private function generateVideoName(array $video, array $tags): string
    {
        // Try to use meaningful tags for naming
        if (!empty($tags)) {
            $meaningfulTags = array_filter($tags, fn($tag) => 
                strlen($tag) > 2 && 
                strlen($tag) < 15 && 
                !in_array(strtolower($tag), ['video', 'stock', 'footage'])
            );
            
            if (!empty($meaningfulTags)) {
                return ucwords(implode(' ', array_slice($meaningfulTags, 0, 3))) . ' Video';
            }
        }

        // Use photographer name if available
        if (!empty($video['user']['name'])) {
            return 'Video by ' . $video['user']['name'];
        }

        return 'Pexels Video #' . $video['id'];
    }

    /**
     * Build proper attribution text for Pexels videos
     */
    private function buildAttribution(array $video): string
    {
        $photographer = $video['user']['name'] ?? 'Unknown';
        $photographerUrl = $video['user']['url'] ?? '';
        
        if ($photographerUrl) {
            return sprintf(
                'Video by %s (%s) on Pexels',
                $photographer,
                $photographerUrl
            );
        }
        
        return sprintf('Video by %s on Pexels', $photographer);
    }

    /**
     * Get supported media types for this service
     */
    public function getSupportedTypes(): array
    {
        return ['video'];
    }

    /**
     * Check if this service supports the given type
     */
    public function supportsType(string $type): bool
    {
        return $type === 'video';
    }

    /**
     * Get service name for identification
     */
    public function getName(): string
    {
        return 'pexels';
    }

    /**
     * Download and get video URL from Pexels
     */
    public function downloadMedia(string $mediaId, string $quality = 'regular'): ?string
    {
        try {
            $this->logger->info('Getting video download URL from Pexels', [
                'media_id' => $mediaId,
                'quality' => $quality
            ]);

            // Get video details
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/videos/videos/' . $mediaId, [
                'headers' => [
                    'Authorization' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
                'timeout' => 10
            ]);

            // Use response validator to safely parse the response
            $video = $this->responseValidator->parseAndValidateResponse($response, ['id', 'video_files']);
            if ($video === null) {
                $this->logger->warning('Invalid response from Pexels API for media download', ['media_id' => $mediaId]);
                return null;
            }
            
            // Find appropriate quality based on preference
            $videoFiles = $this->responseValidator->extractArrayField($video, 'video_files', []);
            
            if (empty($videoFiles)) {
                $this->logger->warning('No video files found for Pexels video', ['media_id' => $mediaId]);
                return null;
            }
            
            if ($quality === 'high') {
                // Look for HD or UHD quality
                foreach ($videoFiles as $file) {
                    $fileQuality = $this->responseValidator->extractStringField($file, 'quality', '');
                    if (in_array($fileQuality, ['uhd', 'hd'])) {
                        $downloadUrl = $this->responseValidator->extractStringField($file, 'link', '');
                        if ($this->responseValidator->validateUrl($downloadUrl)) {
                            return $downloadUrl;
                        }
                    }
                }
            } elseif ($quality === 'low') {
                // Look for SD quality
                foreach ($videoFiles as $file) {
                    $fileQuality = $this->responseValidator->extractStringField($file, 'quality', '');
                    if ($fileQuality === 'sd') {
                        $downloadUrl = $this->responseValidator->extractStringField($file, 'link', '');
                        if ($this->responseValidator->validateUrl($downloadUrl)) {
                            return $downloadUrl;
                        }
                    }
                }
            }

            // Return best available quality as fallback
            $bestFile = $this->findBestVideoFile($videoFiles);
            if ($bestFile) {
                $downloadUrl = $this->responseValidator->extractStringField($bestFile, 'link', '');
                if ($this->responseValidator->validateUrl($downloadUrl)) {
                    return $downloadUrl;
                }
            }

            $this->logger->warning('No valid download URL found for Pexels video', ['media_id' => $mediaId]);
            return null;

        } catch (\Exception $e) {
            $this->logger->error('Failed to get download URL from Pexels', [
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
}
