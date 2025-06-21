<?php

declare(strict_types=1);

namespace App\Service\StockMedia;

use Psr\Log\LoggerInterface;

/**
 * Main coordinator service for stock media operations.
 * 
 * Routes requests to appropriate stock media providers based on
 * media type and coordinates results from multiple providers.
 */
class StockMediaService
{
    /** @var StockMediaServiceInterface[] */
    private array $providers = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ?StockMediaCacheService $cacheService = null,
        ?UnsplashService $unsplashService = null,
        ?IconfinderService $iconfinderService = null,
        ?PexelsService $pexelsService = null,
        ?ShapeService $shapeService = null
    ) {
        // Register available providers
        if ($unsplashService) {
            $this->providers['unsplash'] = $unsplashService;
        }
        if ($iconfinderService) {
            $this->providers['iconfinder'] = $iconfinderService;
        }
        if ($pexelsService) {
            $this->providers['pexels'] = $pexelsService;
        }
        if ($shapeService) {
            $this->providers['shapes'] = $shapeService;
        }
    }

    /**
     * Search stock media across all appropriate providers
     * 
     * @param string $query Search term
     * @param string $type Media type (image, video, icon, etc.)
     * @param int $page Page number
     * @param int $limit Items per page
     * @param array $filters Additional search filters
     * @return array{
     *     items: array,
     *     total: int,
     *     page: int,
     *     limit: int,
     *     hasMore: bool,
     *     providers: array
     * }
     * @throws StockMediaException
     */
    public function search(
        string $query, 
        string $type = 'image', 
        int $page = 1, 
        int $limit = 20, 
        array $filters = []
    ): array {
        $this->logger->info('Starting stock media search', [
            'query' => $query,
            'type' => $type,
            'page' => $page,
            'limit' => $limit,
            'filters' => $filters
        ]);

        $provider = $this->getProviderForType($type);
        
        if (!$provider) {
            throw new StockMediaException(
                "No provider available for media type: {$type}",
                404
            );
        }

        $providerName = $provider->getName();

        // Try to get cached results first
        if ($this->cacheService) {
            $cachedResults = $this->cacheService->getCachedSearchResults(
                $providerName,
                $query,
                $type,
                $page,
                $limit,
                $filters
            );

            if ($cachedResults !== null) {
                $this->logger->info('Stock media search cache hit', [
                    'query' => $query,
                    'type' => $type,
                    'provider' => $providerName,
                    'total_results' => $cachedResults['total'],
                    'returned_items' => count($cachedResults['items'])
                ]);

                // Add provider information to cached results
                $cachedResults['providers'] = [$providerName];
                return $cachedResults;
            }
        }

        try {
            $results = $provider->search($query, $page, $limit, $filters);
            
            // Add provider information to results
            $results['providers'] = [$providerName];

            // Cache the results
            if ($this->cacheService) {
                $this->cacheService->cacheSearchResults(
                    $providerName,
                    $query,
                    $type,
                    $page,
                    $limit,
                    $filters,
                    $results
                );
            }
            
            $this->logger->info('Stock media search completed', [
                'query' => $query,
                'type' => $type,
                'provider' => $providerName,
                'total_results' => $results['total'],
                'returned_items' => count($results['items']),
                'cached' => $this->cacheService !== null
            ]);

            return $results;

        } catch (StockMediaException $e) {
            $this->logger->error('Stock media search failed', [
                'query' => $query,
                'type' => $type,
                'provider' => $providerName,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Search across multiple providers and combine results
     * 
     * @param string $query Search term
     * @param array $types Array of media types to search
     * @param int $page Page number
     * @param int $limit Items per page (divided among providers)
     * @param array $filters Additional search filters
     * @return array Combined results from multiple providers
     */
    public function searchMultiple(
        string $query,
        array $types = ['image'],
        int $page = 1,
        int $limit = 20,
        array $filters = []
    ): array {
        $this->logger->info('Starting multi-provider stock media search', [
            'query' => $query,
            'types' => $types,
            'page' => $page,
            'limit' => $limit
        ]);

        $allResults = [];
        $usedProviders = [];
        $totalItems = 0;
        $itemsPerType = max(1, intval($limit / count($types)));

        foreach ($types as $type) {
            $provider = $this->getProviderForType($type);
            
            if (!$provider) {
                $this->logger->warning('No provider available for type', ['type' => $type]);
                continue;
            }

            try {
                $results = $provider->search($query, $page, $itemsPerType, $filters);
                $allResults = array_merge($allResults, $results['items']);
                $usedProviders[] = $provider->getName();
                $totalItems += $results['total'];
                
            } catch (StockMediaException $e) {
                $this->logger->warning('Provider search failed', [
                    'provider' => $provider->getName(),
                    'type' => $type,
                    'error' => $e->getMessage()
                ]);
                // Continue with other providers
            }
        }

        // Sort results by relevance (you could implement more sophisticated sorting)
        usort($allResults, function($a, $b) {
            // Prefer non-premium items
            if ($a['isPremium'] !== $b['isPremium']) {
                return $a['isPremium'] ? 1 : -1;
            }
            
            // Then sort by source (prefer Unsplash, then others)
            $sourceOrder = ['unsplash' => 1, 'iconfinder' => 2, 'pexels' => 3];
            $aOrder = $sourceOrder[$a['source']] ?? 99;
            $bOrder = $sourceOrder[$b['source']] ?? 99;
            
            return $aOrder <=> $bOrder;
        });

        return [
            'items' => array_slice($allResults, 0, $limit),
            'total' => $totalItems,
            'page' => $page,
            'limit' => $limit,
            'hasMore' => count($allResults) >= $limit,
            'providers' => array_unique($usedProviders)
        ];
    }

    /**
     * Get the appropriate provider for a media type
     */
    private function getProviderForType(string $type): ?StockMediaServiceInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supportsType($type)) {
                return $provider;
            }
        }
        
        return null;
    }

    /**
     * Get all available providers
     * 
     * @return StockMediaServiceInterface[]
     */
    public function getAvailableProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get provider by name
     */
    public function getProvider(string $name): ?StockMediaServiceInterface
    {
        return $this->providers[$name] ?? null;
    }

    /**
     * Check if a provider is available and configured
     */
    public function isProviderAvailable(string $name): bool
    {
        return isset($this->providers[$name]);
    }

    /**
     * Get supported media types across all providers
     */
    public function getSupportedTypes(): array
    {
        $types = [];
        foreach ($this->providers as $provider) {
            $types = array_merge($types, $provider->getSupportedTypes());
        }
        
        return array_unique($types);
    }

    /**
     * Download media from specific provider
     */
    public function downloadMedia(string $providerId, string $mediaId, string $quality = 'regular'): ?string
    {
        $provider = $this->getProvider($providerId);
        
        if (!$provider) {
            $this->logger->error('Provider not found for download', [
                'provider_id' => $providerId,
                'media_id' => $mediaId
            ]);
            return null;
        }

        // Try to get cached download URL first
        if ($this->cacheService) {
            $cachedUrl = $this->cacheService->getCachedDownloadUrl($providerId, $mediaId, $quality);
            
            if ($cachedUrl !== null && isset($cachedUrl['url'])) {
                $this->logger->info('Stock media download URL cache hit', [
                    'provider_id' => $providerId,
                    'media_id' => $mediaId,
                    'quality' => $quality
                ]);
                
                return $cachedUrl['url'];
            }
        }

        try {
            $downloadUrl = $provider->downloadMedia($mediaId, $quality);
            
            // Cache the download URL
            if ($downloadUrl && $this->cacheService) {
                $this->cacheService->cacheDownloadUrl($providerId, $mediaId, $quality, $downloadUrl);
            }
            
            $this->logger->info('Stock media download URL generated', [
                'provider_id' => $providerId,
                'media_id' => $mediaId,
                'quality' => $quality,
                'cached' => $this->cacheService !== null
            ]);
            
            return $downloadUrl;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get download URL', [
                'provider_id' => $providerId,
                'media_id' => $mediaId,
                'quality' => $quality,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Transform external media data to our Media entity format
     */
    public function transformToMediaEntity(array $stockMediaData): array
    {
        return [
            'name' => $stockMediaData['name'],
            'type' => $stockMediaData['type'],
            'mimeType' => $stockMediaData['mimeType'],
            'url' => $stockMediaData['url'],
            'thumbnailUrl' => $stockMediaData['thumbnailUrl'],
            'width' => $stockMediaData['width'],
            'height' => $stockMediaData['height'],
            'size' => $stockMediaData['size'],
            'duration' => $stockMediaData['duration'] ?? null,
            'source' => $stockMediaData['source'],
            'sourceId' => $stockMediaData['sourceId'],
            'license' => $stockMediaData['license'],
            'attribution' => $stockMediaData['attribution'],
            'tags' => $stockMediaData['tags'],
            'isPremium' => $stockMediaData['isPremium'],
            'metadata' => $stockMediaData['metadata']
        ];
    }

    /**
     * Get cache statistics and metrics
     */
    public function getCacheMetrics(): array
    {
        if (!$this->cacheService) {
            return ['cache_enabled' => false];
        }

        return $this->cacheService->getCacheMetrics();
    }

    /**
     * Invalidate cache for a specific provider
     */
    public function invalidateProviderCache(string $providerId): void
    {
        if ($this->cacheService) {
            $this->cacheService->invalidateProviderCache($providerId);
            $this->logger->info('Provider cache invalidated', ['provider' => $providerId]);
        }
    }

    /**
     * Invalidate all stock media cache
     */
    public function invalidateAllCache(): void
    {
        if ($this->cacheService) {
            $this->cacheService->invalidateAllCache();
            $this->logger->info('All stock media cache invalidated');
        }
    }

    /**
     * Check if caching is enabled
     */
    public function isCacheEnabled(): bool
    {
        return $this->cacheService !== null;
    }
}
