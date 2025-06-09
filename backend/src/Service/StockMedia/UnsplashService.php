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

            $params = [
                'query' => $query,
                'page' => $page,
                'per_page' => min($limit, 30), // Unsplash max per page is 30
                'orientation' => $filters['orientation'] ?? null,
                'color' => $filters['color'] ?? null,
                'order_by' => 'relevant'
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

            foreach ($photos as $photo) {
                $transformedPhoto = $this->transformPhotoData($photo);
                if ($transformedPhoto !== null) {
                    $results['items'][] = $transformedPhoto;
                }
            }

            $this->logger->info('Unsplash search completed', [
                'query' => $query,
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

            // Extract and sanitize URLs
            $regularUrl = $this->responseValidator->validateUrl(
                $this->responseValidator->extractStringField($urls, 'regular', '')
            );
            $thumbnailUrl = $this->responseValidator->validateUrl(
                $this->responseValidator->extractStringField($urls, 'small', $regularUrl)
            );
            $previewUrl = $this->responseValidator->validateUrl(
                $this->responseValidator->extractStringField($urls, 'thumb', $thumbnailUrl)
            );

            if (!$regularUrl) {
                $this->logger->warning('Photo has no valid URLs', ['photo_id' => $id]);
                return null;
            }

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
                'url' => $regularUrl,
                'thumbnailUrl' => $thumbnailUrl,
                'previewUrl' => $previewUrl,
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
                    'download_url' => $this->responseValidator->extractStringField($photo, 'links.download', ''),
                    'unsplash_url' => $this->responseValidator->extractStringField($photo, 'links.html', ''),
                    'color' => $this->responseValidator->extractStringField($photo, 'color', '#ffffff'),
                    'blur_hash' => $this->responseValidator->extractStringField($photo, 'blur_hash', ''),
                    'likes' => $this->responseValidator->extractIntField($photo, 'likes', 0),
                    'downloads' => $this->responseValidator->extractIntField($photo, 'downloads', 0),
                    'created_at' => $this->responseValidator->extractStringField($photo, 'created_at', '')
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
}
