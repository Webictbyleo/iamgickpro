<?php

declare(strict_types=1);

namespace App\Service\StockMedia;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Service for integrating with Unsplash API to search stock photos.
 * 
 * Provides high-quality stock photos with proper licensing and attribution.
 * Implements rate limiting and error handling for production use.
 */
class UnsplashService implements StockMediaServiceInterface
{
    private const API_BASE_URL = 'https://api.unsplash.com';
    private const API_VERSION = 'v1';
    
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $accessKey,
        private readonly StockMediaResponseValidator $responseValidator
    ) {}

    /**
     * Search for stock photos on Unsplash
     */
    public function search(string $query, int $page = 1, int $limit = 20, array $filters = []): array
    {
        try {
            $this->logger->info('Searching Unsplash for stock photos', [
                'query' => $query,
                'page' => $page,
                'limit' => $limit,
                'filters' => $filters
            ]);

            // Implement variety strategies to avoid repetitive results
            $orderBy = $this->getVariedOrderBy($filters);
            $enhancedQuery = $this->enhanceQueryForVariety($query, $filters);

            $params = [
                'query' => $enhancedQuery,
                'page' => $page,
                'per_page' => min($limit, 30), // Unsplash max per page is 30
                'orientation' => $filters['orientation'] ?? null,
                'color' => $filters['color'] ?? null,
                'order_by' => $orderBy
            ];

            // Remove null values
            $params = array_filter($params, fn($value) => $value !== null);

            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/search/photos', [
                'headers' => [
                    'Authorization' => 'Client-ID ' . $this->accessKey,
                    'Accept-Version' => self::API_VERSION,
                ],
                'query' => $params,
                'timeout' => 10
            ]);

            // Use the response validator for robust JSON parsing
            $data = $this->responseValidator->parseAndValidateResponse(
                $response,
                ['results', 'total'], // Required fields
                'unsplash'
            );
            
            $results = [
                'items' => [],
                'total' => $this->responseValidator->extractIntField($data, 'total', 0),
                'page' => $page,
                'limit' => $limit,
                'hasMore' => $this->responseValidator->extractIntField($data, 'total_pages', 0) > $page
            ];

            // Extract and validate photos array
            $photos = $this->responseValidator->extractItemsArray(
                $data, 
                'results',
                ['id', 'urls'] // Required fields for each photo
            );

            // Apply additional randomization if needed
            if ($this->shouldRandomizeResults($filters)) {
                $photos = $this->randomizePhotos($photos);
            }

            foreach ($photos as $photo) {
                $transformedPhoto = $this->transformPhotoData($photo);
                
                if ($transformedPhoto !== null) {
                    $results['items'][] = $transformedPhoto;
                }
            }

            $this->logger->info('Unsplash search completed', [
                'query' => $enhancedQuery,
                'order_by' => $orderBy,
                'total_results' => $results['total'],
                'returned_items' => count($results['items'])
            ]);

            return $results;

        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Unsplash API client error', [
                'query' => $query,
                'error' => $e->getMessage(),
                'status_code' => $e->getResponse()->getStatusCode()
            ]);
            
            throw new StockMediaException(
                'Failed to search Unsplash: ' . $e->getMessage(),
                $e->getResponse()->getStatusCode(),
                null,
                'unsplash'
            );
            
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Unsplash API transport error', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            throw new StockMediaException(
                'Failed to connect to Unsplash API: ' . $e->getMessage(),
                500,
                null,
                'unsplash'
            );
        }
    }

    /**
     * Transform Unsplash photo data to our standard format with validation
     */
    private function transformPhotoData(array $photo): ?array
    {
        try {
            // Validate essential fields exist
            $id = $this->responseValidator->extractStringField($photo, 'id', null);
            $urls = $this->responseValidator->extractArrayField($photo, 'urls', []);
            
            if (empty($id) || empty($urls)) {
                $this->logger->warning('Photo missing essential fields', [
                    'photo_id' => $id,
                    'has_urls' => !empty($urls)
                ]);
                return null;
            }
            
            // Extract and validate URLs without sanitization
            $regularUrl = $this->responseValidator->validateUrl(
                $this->responseValidator->extractUrlField($urls, 'regular', '')
            );
            $thumbnailUrl = $this->responseValidator->validateUrl(
                $this->responseValidator->extractUrlField($urls, 'thumb', $regularUrl)
            );
            $previewUrl = $this->responseValidator->validateUrl(
                $this->responseValidator->extractUrlField($urls, 'small', $thumbnailUrl)
            );

            if (!$regularUrl) {
                $this->logger->warning('Photo has no valid URLs', ['photo_id' => $id]);
                return null;
            }

            // Create proxied URLs for main images to avoid CORS and bandwidth issues
            $proxiedRegularUrl = $this->createProxiedUrl($regularUrl);
            $proxiedThumbnailUrl = $this->createProxiedUrl($thumbnailUrl);
            $proxiedPreviewUrl = $this->createProxiedUrl($previewUrl);

            // Extract and sanitize text fields
            $description = $this->responseValidator->sanitizeString(
                $this->responseValidator->extractStringField($photo, 'description', '')
            );
            $altDescription = $this->responseValidator->sanitizeString(
                $this->responseValidator->extractStringField($photo, 'alt_description', '')
            );

            // Build tags safely
            $tags = [];
            
            if ($description) {
                $descriptionWords = array_filter(
                    explode(' ', strtolower($description)),
                    fn($word) => strlen($word) > 2 && strlen($word) < 20
                );
                $tags = array_merge($tags, array_slice($descriptionWords, 0, 5));
            }
            
            if ($altDescription) {
                $altWords = array_filter(
                    explode(' ', strtolower($altDescription)),
                    fn($word) => strlen($word) > 2 && strlen($word) < 20
                );
                $tags = array_merge($tags, array_slice($altWords, 0, 3));
            }

            // Add category-based tags
            $categories = $this->responseValidator->extractArrayField($photo, 'categories', []);
            if (!empty($categories)) {
                $tags = array_merge($tags, array_slice($categories, 0, 3));
            }

            // Extract dimensions safely
            $width = $this->responseValidator->extractIntField($photo, 'width', 1920);
            $height = $this->responseValidator->extractIntField($photo, 'height', 1080);

            // Extract user information safely
            $user = $this->responseValidator->extractArrayField($photo, 'user', []);
            $userName = $this->responseValidator->sanitizeString(
                $this->responseValidator->extractStringField($user, 'name', 'Unknown')
            );
            $userUsername = $this->responseValidator->sanitizeString(
                $this->responseValidator->extractStringField($user, 'username', 'unknown')
            );

            // Clean and deduplicate tags
            $tags = array_unique(array_filter($tags, fn($tag) => strlen($tag) > 1 && strlen($tag) < 30));
            
            return [
                'id' => $id,
                'name' => $altDescription ?: $description ?: "Photo by {$userName}",
                'type' => 'image',
                'mimeType' => 'image/jpeg',
                'url' => $proxiedRegularUrl ?: $regularUrl, // Use proxied URL with fallback
                'thumbnailUrl' => $proxiedThumbnailUrl ?: $thumbnailUrl,
                'previewUrl' => $proxiedPreviewUrl ?: $previewUrl,
                'width' => max(1, $width),
                'height' => max(1, $height),
                'size' => null,
                'source' => 'unsplash',
                'sourceId' => $id,
                'license' => 'Unsplash License',
                'attribution' => "Photo by {$userName} on Unsplash",
                'tags' => array_values($tags),
                'isPremium' => false,
                'metadata' => [
                    'photographer' => $userName,
                    'photographer_username' => $userUsername,
                    'download_url' => $this->responseValidator->extractUrlField($photo, 'links.download', ''),
                    'unsplash_url' => $this->responseValidator->extractUrlField($photo, 'links.html', ''),
                    'color' => $this->responseValidator->extractStringField($photo, 'color', '#ffffff'),
                    'blur_hash' => $this->responseValidator->extractStringField($photo, 'blur_hash', ''),
                    'likes' => $this->responseValidator->extractIntField($photo, 'likes', 0),
                    'downloads' => $this->responseValidator->extractIntField($photo, 'downloads', 0),
                    'created_at' => $this->responseValidator->extractStringField($photo, 'created_at', ''),
                    'original_urls' => [
                        'regular' => $regularUrl,
                        'thumbnail' => $thumbnailUrl,
                        'preview' => $previewUrl
                    ]
                ]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to transform photo data', [
                'photo_id' => $photo['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get supported media types for this service
     */
    public function getSupportedTypes(): array
    {
        return ['image'];
    }

    /**
     * Check if this service supports the given type
     */
    public function supportsType(string $type): bool
    {
        return $type === 'image';
    }

    /**
     * Get service name for identification
     */
    public function getName(): string
    {
        return 'unsplash';
    }

    /**
     * Download and store a photo from Unsplash with validation
     */
    public function downloadMedia(string $mediaId, string $quality = 'regular'): ?string
    {
        try {
            $this->logger->info('Downloading media from Unsplash', [
                'media_id' => $mediaId,
                'quality' => $quality
            ]);

            // Get photo details first
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/photos/' . $mediaId, [
                'headers' => [
                    'Authorization' => 'Client-ID ' . $this->accessKey,
                    'Accept-Version' => self::API_VERSION,
                ],
                'timeout' => 10
            ]);

            // Validate the response
            $photo = $this->responseValidator->parseAndValidateResponse(
                $response,
                ['urls'],
                'unsplash'
            );

            // Extract and validate download URL
            $urls = $this->responseValidator->extractArrayField($photo, 'urls', []);
            $downloadUrl = $this->responseValidator->validateUrl(
                $this->responseValidator->extractStringField($urls, $quality, 
                    $this->responseValidator->extractStringField($urls, 'regular', '')
                )
            );

            if (!$downloadUrl) {
                $this->logger->warning('No valid download URL found', [
                    'media_id' => $mediaId,
                    'quality' => $quality,
                    'available_urls' => array_keys($urls)
                ]);
                return null;
            }

            // Track download for Unsplash analytics
            $downloadLocation = $this->responseValidator->extractStringField($photo, 'links.download_location', '');
            if ($downloadLocation) {
                try {
                    $this->httpClient->request('GET', $downloadLocation, [
                        'headers' => [
                            'Authorization' => 'Client-ID ' . $this->accessKey,
                        ],
                        'timeout' => 5
                    ]);
                } catch (\Exception $e) {
                    // Don't fail the main request if analytics tracking fails
                    $this->logger->debug('Failed to track download for Unsplash analytics', [
                        'media_id' => $mediaId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $downloadUrl;

        } catch (\Exception $e) {
            $this->logger->error('Failed to download media from Unsplash', [
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Create a proxied URL for Unsplash images to avoid CORS and bandwidth issues
     */
    private function createProxiedUrl(?string $originalUrl): ?string
    {
        if (!$originalUrl || !$this->responseValidator->validateUrl($originalUrl)) {
            return null;
        }

        // Encode the URL for safe transmission
        $encodedUrl = base64_encode($originalUrl);
        
        // Return proxied URL through our media controller
        return '/api/media/proxy/' . $encodedUrl;
    }

    /**
     * Get varied order by parameter to avoid repetitive results
     */
    private function getVariedOrderBy(array $filters): string
    {
        // If specific order is requested, use it
        if (isset($filters['order_by'])) {
            return $filters['order_by'];
        }

        // For default searches (especially repeated ones), vary the order
        $orderOptions = ['relevant', 'latest', 'popular'];
        
        // Use current hour to create time-based variation
        $hourSeed = (int) date('H');
        $dayOfYearSeed = (int) date('z');
        
        // Combine seeds for better distribution
        $seedIndex = ($hourSeed + $dayOfYearSeed) % count($orderOptions);
        
        return $orderOptions[$seedIndex];
    }

    /**
     * Enhance query with variety to get different results for same base query
     */
    private function enhanceQueryForVariety(string $query, array $filters): string
    {
        // If query is very specific or long, don't modify it
        if (strlen($query) > 20 || str_word_count($query) > 3) {
            return $query;
        }

        // For broad/default queries, add variety terms occasionally
        $varietyTerms = [
            'business' => ['professional', 'office', 'corporate', 'meeting', 'teamwork'],
            'nature' => ['landscape', 'outdoor', 'wildlife', 'forest', 'mountain'],
            'technology' => ['digital', 'computer', 'innovation', 'tech', 'modern'],
            'people' => ['person', 'human', 'portrait', 'lifestyle', 'community'],
            'background' => ['abstract', 'texture', 'minimal', 'pattern', 'clean']
        ];

        $baseTerm = strtolower(trim($query));
        
        // 30% chance to add variety term for common queries
        if (isset($varietyTerms[$baseTerm]) && rand(1, 10) <= 3) {
            $varietyOptions = $varietyTerms[$baseTerm];
            $selectedVariety = $varietyOptions[array_rand($varietyOptions)];
            return $query . ' ' . $selectedVariety;
        }

        return $query;
    }

    /**
     * Determine if results should be randomized
     */
    private function shouldRandomizeResults(array $filters): bool
    {
        // Don't randomize if specific order was requested
        if (isset($filters['order_by'])) {
            return false;
        }

        // Randomize 20% of the time for default searches
        return rand(1, 100) <= 20;
    }

    /**
     * Randomize photo order while maintaining quality
     */
    private function randomizePhotos(array $photos): array
    {
        // Don't randomize if we have few results
        if (count($photos) <= 3) {
            return $photos;
        }

        // Keep first few results (usually highest quality) and randomize the rest
        $keepFirst = min(3, count($photos));
        $firstPhotos = array_slice($photos, 0, $keepFirst);
        $restPhotos = array_slice($photos, $keepFirst);
        
        // Shuffle the remaining photos
        shuffle($restPhotos);
        
        return array_merge($firstPhotos, $restPhotos);
    }
}
