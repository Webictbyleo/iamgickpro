<?php

declare(strict_types=1);

namespace App\Service\StockMedia;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Service for integrating with Iconfinder API to search stock icons.
 * 
 * Provides high-quality vector icons with various licensing options.
 * Implements rate limiting and error handling for production use.
 */
class IconfinderService implements StockMediaServiceInterface
{
    private const API_BASE_URL = 'https://api.iconfinder.com/v4';
    
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
        private readonly StockMediaResponseValidator $responseValidator
    ) {}

    /**
     * Search for stock icons on Iconfinder
     */
    public function search(string $query, int $page = 1, int $limit = 20, array $filters = []): array
    {
        try {
            $this->logger->info('Searching Iconfinder for stock icons', [
                'query' => $query,
                'page' => $page,
                'limit' => $limit,
                'filters' => $filters
            ]);

            $params = [
                'query' => $query,
                'offset' => ($page - 1) * $limit,
                'count' => min($limit, 100), // Iconfinder max per request is 100
                'license' => 'free,commercial', // Include both free and commercial icons
                'vector' => 1, // Prefer vector formats
                'minimum_size' => 32,
                'maximum_size' => 512
            ];

            // Add style filters if provided
            if (!empty($filters['style'])) {
                $params['style'] = $filters['style'];
            }

            // Add category filter if provided
            if (!empty($filters['category'])) {
                $params['category'] = $filters['category'];
            }

            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/icons/search', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
                'query' => $params,
                'timeout' => 10
            ]);
           

            // Use response validator to safely parse JSON response
            $data = $this->responseValidator->parseAndValidateResponse(
                $response,
                ['total_count', 'icons'], // Required fields
                'iconfinder'
            );

            if ($data === null) {
                $this->logger->warning('Invalid response from Iconfinder API', ['query' => $query]);
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
                'total' => $this->responseValidator->extractIntField($data, 'total_count', 0),
                'page' => $page,
                'limit' => $limit,
                'hasMore' => ($this->responseValidator->extractIntField($data, 'total_count', 0)) > ($page * $limit)
            ];

            // Validate and extract icons array with required fields
            $icons = $this->responseValidator->extractItemsArray($data, 'icons', ['icon_id']);
            foreach ($icons as $icon) {
                $transformedIcon = $this->transformIconData($icon);
                if ($transformedIcon !== null) {
                    $results['items'][] = $transformedIcon;
                }
            }

            $this->logger->info('Iconfinder search completed', [
                'query' => $query,
                'total_results' => $results['total'],
                'returned_items' => count($results['items'])
            ]);

            return $results;

        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Iconfinder API client error', [
                'query' => $query,
                'error' => $e->getMessage(),
                'status_code' => $e->getResponse()->getStatusCode()
            ]);
            
            throw new StockMediaException(
                'Failed to search Iconfinder: ' . $e->getMessage(),
                $e->getResponse()->getStatusCode(),
                null,
                'iconfinder'
            );
            
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Iconfinder API transport error', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            throw new StockMediaException(
                'Failed to connect to Iconfinder API: ' . $e->getMessage(),
                500,
                null,
                'iconfinder'
            );
        }
    }

    /**
     * Transform Iconfinder icon data to our standard format
     */
    private function transformIconData(array $icon): ?array
    {
        try {
            // Validate required fields
            $iconId = $this->responseValidator->extractStringField($icon, 'icon_id', null);
            if (!$iconId) {
                $this->logger->warning('Iconfinder icon missing required icon_id', ['icon' => $icon]);
                return null;
            }

            // Find the best quality icon format
            $rasterSizes = $this->responseValidator->extractArrayField($icon, 'raster_sizes', []);
            $vectorSizes = $this->responseValidator->extractArrayField($icon, 'vector_sizes', []);
            
            $bestFormat = $this->findBestIconFormat($rasterSizes);
            $vectorFormat = $this->findVectorFormat($vectorSizes);

            // Prefer vector format if available
            $primaryFormat = $vectorFormat ?: $bestFormat;
            
            if (!$primaryFormat) {
                $this->logger->warning('No valid format found for Iconfinder icon', ['icon_id' => $iconId]);
                return null;
            }
            
            // Extract tags from categories and styles
            $tags = [];
            $categories = $this->responseValidator->extractArrayField($icon, 'categories', []);
            foreach ($categories as $category) {
                $categoryName = $this->responseValidator->extractStringField($category, 'name', null);
                if ($categoryName) {
                    $tags[] = $this->responseValidator->sanitizeString($categoryName);
                }
            }
            
            $styles = $this->responseValidator->extractArrayField($icon, 'styles', []);
            foreach ($styles as $style) {
                $styleName = $this->responseValidator->extractStringField($style, 'name', null);
                if ($styleName) {
                    $tags[] = $this->responseValidator->sanitizeString($styleName);
                }
            }

            // Add search-related tags
            $iconTags = $icon['tags'] ?? null; // Handle mixed type directly
            if ($iconTags) {
                $tagsArray = is_array($iconTags) ? $iconTags : explode(',', (string)$iconTags);
                foreach ($tagsArray as $tag) {
                    $cleanTag = $this->responseValidator->sanitizeString(trim((string)$tag));
                    if (strlen($cleanTag) > 1) {
                        $tags[] = $cleanTag;
                    }
                }
            }

            $tags = array_unique(array_filter($tags, fn($tag) => strlen($tag) > 1));

            // Extract and validate URLs
            $downloadUrl = $this->responseValidator->extractStringField($primaryFormat, 'download_url', null);
            $previewUrl = $this->responseValidator->extractStringField($primaryFormat, 'preview_url', null) 
                ?? $this->responseValidator->extractStringField($bestFormat, 'preview_url', null);
            
            if (!$this->responseValidator->validateUrl($downloadUrl) && !$this->responseValidator->validateUrl($previewUrl)) {
                $this->logger->warning('No valid URLs found for Iconfinder icon', ['icon_id' => $iconId]);
                return null;
            }

            // Build attribution safely
            $attribution = $this->buildAttribution($icon);

            // Only proxy download URLs that require authentication
            // Thumbnail and preview URLs work fine directly
            $proxiedUrl = $this->createProxiedUrl($downloadUrl);
            $directThumbnailUrl = $this->responseValidator->extractStringField($bestFormat, 'preview_url', null) ?: $previewUrl;
            $directPreviewUrl = $previewUrl;

            return [
                'id' => $iconId,
                'name' => $this->generateIconName($icon, $tags),
                'type' => 'icon',
                'mimeType' => $vectorFormat ? 'image/svg+xml' : 'image/png',
                'url' => $proxiedUrl ?: $directPreviewUrl, // Fallback to preview if proxy fails
                'thumbnailUrl' => $directThumbnailUrl,
                'previewUrl' => $directPreviewUrl,
                'width' => $this->responseValidator->extractIntField($primaryFormat, 'size', 64),
                'height' => $this->responseValidator->extractIntField($primaryFormat, 'size', 64),
                'size' => null, // Iconfinder doesn't provide file size in search
                'source' => 'iconfinder',
                'sourceId' => $iconId,
                'license' => $this->determineLicense($icon),
                'attribution' => $attribution,
                'tags' => array_values($tags),
                'isPremium' => $this->responseValidator->extractBoolField($icon, 'is_premium', false),
                'metadata' => [
                    'icon_id' => $iconId,
                    'type' => $this->responseValidator->extractStringField($icon, 'type', 'icon'),
                    'containers' => $this->responseValidator->extractArrayField($icon, 'containers', []),
                    'styles' => $styles,
                    'categories' => $categories,
                    'published_at' => $this->responseValidator->extractStringField($icon, 'published_at', null),
                    'is_icon_glyph' => $this->responseValidator->extractBoolField($icon, 'is_icon_glyph', false),
                    'vector_available' => !empty($vectorFormat),
                    'formats_available' => array_keys($rasterSizes),
                    'original_urls' => [
                        'download' => $downloadUrl,
                        'preview' => $previewUrl,
                        'thumbnail' => $this->responseValidator->extractStringField($bestFormat, 'preview_url', null)
                    ],
                    'proxy_info' => [
                        'download_proxied' => !empty($downloadUrl),
                        'preview_direct' => true,
                        'thumbnail_direct' => true
                    ]
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to transform Iconfinder icon data', [
                'icon' => $icon,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Find the best quality raster format
     */
    private function findBestIconFormat(array $rasterSizes): ?array
    {
        if (empty($rasterSizes)) {
            return null;
        }

        // Preferred sizes in order
        $preferredSizes = [128, 64, 256, 512, 32, 48, 96];
        
        // Convert array to size-indexed format for easier lookup
        $sizeMap = [];
        foreach ($rasterSizes as $rasterSize) {
            $size = $rasterSize['size'] ?? null;
            if ($size && !empty($rasterSize['formats'])) {
                // Get the first format (usually PNG)
                $format = $rasterSize['formats'][0];
                $sizeMap[$size] = $format + ['size' => $size];
            }
        }
        
        foreach ($preferredSizes as $size) {
            if (isset($sizeMap[$size])) {
                return $sizeMap[$size];
            }
        }

        // Return first available if none of preferred sizes found
        return !empty($sizeMap) ? reset($sizeMap) : null;
    }

    /**
     * Find vector format if available
     */
    private function findVectorFormat(array $vectorSizes): ?array
    {
        if (empty($vectorSizes)) {
            return null;
        }

        // Look for SVG format first
        foreach ($vectorSizes as $vectorSize) {
            if (!empty($vectorSize['formats'])) {
                foreach ($vectorSize['formats'] as $format) {
                    if (isset($format['format']) && strtolower($format['format']) === 'svg') {
                        return $format + ['size' => $vectorSize['size'] ?? 512];
                    }
                }
            }
        }

        // Return first vector format if SVG not found
        foreach ($vectorSizes as $vectorSize) {
            if (!empty($vectorSize['formats'])) {
                $format = $vectorSize['formats'][0];
                return $format + ['size' => $vectorSize['size'] ?? 512];
            }
        }

        return null;
    }

    /**
     * Generate a meaningful name for the icon
     */
    private function generateIconName(array $icon, array $tags): string
    {
        // Try to use existing tags for naming
        if (!empty($tags)) {
            $meaningfulTags = array_filter($tags, fn($tag) => strlen($tag) > 2 && strlen($tag) < 20);
            if (!empty($meaningfulTags)) {
                return ucwords(implode(' ', array_slice($meaningfulTags, 0, 3))) . ' Icon';
            }
        }

        // Fallback to categories
        if (!empty($icon['categories'])) {
            $category = is_array($icon['categories'][0]) 
                ? $icon['categories'][0]['name'] 
                : $icon['categories'][0];
            return ucwords($category) . ' Icon';
        }

        return 'Icon #' . $icon['icon_id'];
    }

    /**
     * Determine license type for the icon
     */
    private function determineLicense(array $icon): string
    {
        if ($icon['is_premium'] ?? false) {
            return 'Premium Commercial License';
        }

        // Check license types
        $licenses = $icon['licenses'] ?? [];
        if (!empty($licenses)) {
            foreach ($licenses as $license) {
                if (isset($license['name'])) {
                    return $license['name'];
                }
            }
        }

        return 'Free License';
    }

    /**
     * Build proper attribution text for Iconfinder icons
     */
    private function buildAttribution(array $icon): string
    {
        $iconId = $icon['icon_id'];
        
        if (!empty($icon['licenses'])) {
            $license = $icon['licenses'][0];
            if (isset($license['name'])) {
                return "Icon #{$iconId} by Iconfinder ({$license['name']})";
            }
        }

        return "Icon #{$iconId} by Iconfinder";
    }

    /**
     * Get supported media types for this service
     */
    public function getSupportedTypes(): array
    {
        return ['icon'];
    }

    /**
     * Check if this service supports the given type
     */
    public function supportsType(string $type): bool
    {
        return $type === 'icon';
    }

    /**
     * Get service name for identification
     */
    public function getName(): string
    {
        return 'iconfinder';
    }

    /**
     * Download and get icon URL from Iconfinder
     */
    public function downloadMedia(string $mediaId, string $quality = 'regular'): ?string
    {
        try {
            $this->logger->info('Getting icon download URL from Iconfinder', [
                'media_id' => $mediaId,
                'quality' => $quality
            ]);

            // Get icon details
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/icons/' . $mediaId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
                'timeout' => 10
            ]);

            // Use response validator to safely parse the response
            $icon = $this->responseValidator->parseAndValidateResponse($response, ['icon_id']);
            if ($icon === null) {
                $this->logger->warning('Invalid response from Iconfinder API for media download', ['media_id' => $mediaId]);
                return null;
            }
            
            // Determine best download URL based on quality preference
            $rasterSizes = $this->responseValidator->extractArrayField($icon, 'raster_sizes', []);
            $vectorSizes = $this->responseValidator->extractArrayField($icon, 'vector_sizes', []);

            // For 'high' quality, prefer vector or largest raster
            if ($quality === 'high' && !empty($vectorSizes)) {
                $vectorFormat = $this->findVectorFormat($vectorSizes);
                if ($vectorFormat) {
                    $downloadUrl = $this->responseValidator->extractStringField($vectorFormat, 'download_url', '');
                    if ($this->responseValidator->validateUrl($downloadUrl)) {
                        return $downloadUrl;
                    }
                }
            }

            // Find appropriate raster size
            $format = $this->findBestIconFormat($rasterSizes);
            if ($format) {
                $downloadUrl = $this->responseValidator->extractStringField($format, 'download_url', '');
                if ($this->responseValidator->validateUrl($downloadUrl)) {
                    return $downloadUrl;
                }
            }

            $this->logger->warning('No valid download URL found for Iconfinder icon', ['media_id' => $mediaId]);
            return null;

        } catch (\Exception $e) {
            $this->logger->error('Failed to get download URL from Iconfinder', [
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Create a proxied URL for external media that requires authentication (like download URLs)
     * Returns null if the URL is not suitable for proxying or if proxying fails
     */
    private function createProxiedUrl(?string $originalUrl): ?string
    {
        if (!$originalUrl || !$this->responseValidator->validateUrl($originalUrl)) {
            return null;
        }

        // Only proxy URLs that typically require authentication
        // Skip preview/thumbnail URLs that work directly
        if (str_contains($originalUrl, 'preview') || str_contains($originalUrl, 'thumbnail')) {
            return null; // Don't proxy preview/thumbnail URLs
        }

        // Encode the URL for safe transmission
        $encodedUrl = base64_encode($originalUrl);
        
        // Return proxied URL through our media controller
        return '/api/media/proxy/' . $encodedUrl;
    }
}
