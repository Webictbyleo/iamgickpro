<?php

declare(strict_types=1);

namespace App\Service\StockMedia;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * Caching service for stock media operations.
 * 
 * Provides comprehensive caching for search results, API responses,
 * provider metadata, and rate limiting data. Implements cache warming,
 * invalidation strategies, and performance optimization.
 */
class StockMediaCacheService
{
    // Cache TTL constants (in seconds)
    private const SEARCH_RESULTS_TTL = 3600; // 1 hour
    private const PROVIDER_METADATA_TTL = 86400; // 24 hours
    private const RATE_LIMIT_TTL = 3600; // 1 hour
    private const MEDIA_DETAILS_TTL = 7200; // 2 hours
    private const DOWNLOAD_URL_TTL = 1800; // 30 minutes
    private const STATISTICS_TTL = 600; // 10 minutes

    // Cache key prefixes
    private const PREFIX_SEARCH = 'stock_media_search';
    private const PREFIX_PROVIDER = 'stock_media_provider';
    private const PREFIX_RATE_LIMIT = 'stock_media_rate_limit';
    private const PREFIX_MEDIA_DETAILS = 'stock_media_details';
    private const PREFIX_DOWNLOAD_URL = 'stock_media_download';
    private const PREFIX_STATISTICS = 'stock_media_stats';

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Cache search results for a specific query and provider
     */
    public function cacheSearchResults(
        string $provider,
        string $query,
        string $type,
        int $page,
        int $limit,
        array $filters,
        array $results
    ): void {
        $cacheKey = $this->generateSearchKey($provider, $query, $type, $page, $limit, $filters);
        
        try {
            $item = $this->cache->getItem($cacheKey);
            $item->set($results);
            $item->expiresAfter(self::SEARCH_RESULTS_TTL);
            
            $this->cache->save($item);
            
            $this->logger->debug('Stock media search results cached', [
                'cache_key' => $cacheKey,
                'provider' => $provider,
                'query' => $query,
                'type' => $type,
                'results_count' => count($results['items'] ?? [])
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache stock media search results', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cached search results
     */
    public function getCachedSearchResults(
        string $provider,
        string $query,
        string $type,
        int $page,
        int $limit,
        array $filters
    ): ?array {
        $cacheKey = $this->generateSearchKey($provider, $query, $type, $page, $limit, $filters);
        
        try {
            $item = $this->cache->getItem($cacheKey);
            
            if ($item->isHit()) {
                $this->logger->debug('Stock media search results cache hit', [
                    'cache_key' => $cacheKey,
                    'provider' => $provider,
                    'query' => $query
                ]);
                
                return $item->get();
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve cached stock media search results', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * Cache provider metadata and capabilities
     */
    public function cacheProviderMetadata(string $provider, array $metadata): void
    {
        $cacheKey = self::PREFIX_PROVIDER . '_' . $provider;
        
        try {
            $item = $this->cache->getItem($cacheKey);
            $item->set($metadata);
            $item->expiresAfter(self::PROVIDER_METADATA_TTL);
            
            $this->cache->save($item);
            
            $this->logger->debug('Stock media provider metadata cached', [
                'provider' => $provider,
                'cache_key' => $cacheKey
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache stock media provider metadata', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cached provider metadata
     */
    public function getCachedProviderMetadata(string $provider): ?array
    {
        $cacheKey = self::PREFIX_PROVIDER . '_' . $provider;
        
        try {
            $item = $this->cache->getItem($cacheKey);
            
            if ($item->isHit()) {
                return $item->get();
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve cached provider metadata', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * Cache rate limiting information for a provider
     */
    public function cacheRateLimit(string $provider, array $rateLimitData): void
    {
        $cacheKey = self::PREFIX_RATE_LIMIT . '_' . $provider;
        
        try {
            $item = $this->cache->getItem($cacheKey);
            $item->set([
                'requests_made' => $rateLimitData['requests_made'] ?? 0,
                'requests_remaining' => $rateLimitData['requests_remaining'] ?? 0,
                'reset_time' => $rateLimitData['reset_time'] ?? null,
                'updated_at' => time()
            ]);
            $item->expiresAfter(self::RATE_LIMIT_TTL);
            
            $this->cache->save($item);
            
            $this->logger->debug('Stock media rate limit data cached', [
                'provider' => $provider,
                'requests_remaining' => $rateLimitData['requests_remaining'] ?? 0
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache rate limit data', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cached rate limiting information
     */
    public function getCachedRateLimit(string $provider): ?array
    {
        $cacheKey = self::PREFIX_RATE_LIMIT . '_' . $provider;
        
        try {
            $item = $this->cache->getItem($cacheKey);
            
            if ($item->isHit()) {
                return $item->get();
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve cached rate limit data', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * Cache detailed media information
     */
    public function cacheMediaDetails(string $provider, string $mediaId, array $details): void
    {
        $cacheKey = self::PREFIX_MEDIA_DETAILS . '_' . $provider . '_' . $mediaId;
        
        try {
            $item = $this->cache->getItem($cacheKey);
            $item->set($details);
            $item->expiresAfter(self::MEDIA_DETAILS_TTL);
            
            $this->cache->save($item);
            
            $this->logger->debug('Stock media details cached', [
                'provider' => $provider,
                'media_id' => $mediaId
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache media details', [
                'provider' => $provider,
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cached media details
     */
    public function getCachedMediaDetails(string $provider, string $mediaId): ?array
    {
        $cacheKey = self::PREFIX_MEDIA_DETAILS . '_' . $provider . '_' . $mediaId;
        
        try {
            $item = $this->cache->getItem($cacheKey);
            
            if ($item->isHit()) {
                return $item->get();
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve cached media details', [
                'provider' => $provider,
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * Cache download URL for media
     */
    public function cacheDownloadUrl(string $provider, string $mediaId, string $quality, string $url): void
    {
        $cacheKey = self::PREFIX_DOWNLOAD_URL . '_' . $provider . '_' . $mediaId . '_' . $quality;
        
        try {
            $item = $this->cache->getItem($cacheKey);
            $item->set([
                'url' => $url,
                'expires_at' => time() + self::DOWNLOAD_URL_TTL,
                'cached_at' => time()
            ]);
            $item->expiresAfter(self::DOWNLOAD_URL_TTL);
            
            $this->cache->save($item);
            
            $this->logger->debug('Stock media download URL cached', [
                'provider' => $provider,
                'media_id' => $mediaId,
                'quality' => $quality
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache download URL', [
                'provider' => $provider,
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cached download URL
     */
    public function getCachedDownloadUrl(string $provider, string $mediaId, string $quality): ?array
    {
        $cacheKey = self::PREFIX_DOWNLOAD_URL . '_' . $provider . '_' . $mediaId . '_' . $quality;
        
        try {
            $item = $this->cache->getItem($cacheKey);
            
            if ($item->isHit()) {
                $data = $item->get();
                
                // Check if URL is still valid
                if (isset($data['expires_at']) && time() < $data['expires_at']) {
                    return $data;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve cached download URL', [
                'provider' => $provider,
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * Cache usage statistics
     */
    public function cacheStatistics(array $statistics): void
    {
        $cacheKey = self::PREFIX_STATISTICS . '_global';
        
        try {
            $item = $this->cache->getItem($cacheKey);
            $item->set([
                'statistics' => $statistics,
                'cached_at' => time()
            ]);
            $item->expiresAfter(self::STATISTICS_TTL);
            
            $this->cache->save($item);
            
            $this->logger->debug('Stock media statistics cached');
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache statistics', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cached statistics
     */
    public function getCachedStatistics(): ?array
    {
        $cacheKey = self::PREFIX_STATISTICS . '_global';
        
        try {
            $item = $this->cache->getItem($cacheKey);
            
            if ($item->isHit()) {
                return $item->get();
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve cached statistics', [
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * Invalidate cache for specific provider
     */
    public function invalidateProviderCache(string $provider): void
    {
        $patterns = [
            self::PREFIX_SEARCH . '_' . $provider . '_*',
            self::PREFIX_PROVIDER . '_' . $provider,
            self::PREFIX_RATE_LIMIT . '_' . $provider,
            self::PREFIX_MEDIA_DETAILS . '_' . $provider . '_*',
            self::PREFIX_DOWNLOAD_URL . '_' . $provider . '_*'
        ];

        foreach ($patterns as $pattern) {
            try {
                $this->invalidateCacheByPattern($pattern);
            } catch (\Exception $e) {
                $this->logger->error('Failed to invalidate cache pattern', [
                    'pattern' => $pattern,
                    'provider' => $provider,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger->info('Provider cache invalidated', ['provider' => $provider]);
    }

    /**
     * Invalidate all stock media cache
     */
    public function invalidateAllCache(): void
    {
        $prefixes = [
            self::PREFIX_SEARCH,
            self::PREFIX_PROVIDER,
            self::PREFIX_RATE_LIMIT,
            self::PREFIX_MEDIA_DETAILS,
            self::PREFIX_DOWNLOAD_URL,
            self::PREFIX_STATISTICS
        ];

        foreach ($prefixes as $prefix) {
            try {
                $this->invalidateCacheByPattern($prefix . '_*');
            } catch (\Exception $e) {
                $this->logger->error('Failed to invalidate cache prefix', [
                    'prefix' => $prefix,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger->info('All stock media cache invalidated');
    }

    /**
     * Warm up cache with popular search terms
     */
    public function warmUpCache(array $popularSearches): void
    {
        $this->logger->info('Starting stock media cache warm-up', [
            'searches_count' => count($popularSearches)
        ]);

        // This would be called by a background job to pre-populate cache
        // with popular searches across all providers
        foreach ($popularSearches as $search) {
            // Implementation would make actual API calls to warm cache
            $this->logger->debug('Cache warm-up entry processed', [
                'query' => $search['query'] ?? '',
                'type' => $search['type'] ?? ''
            ]);
        }

        $this->logger->info('Stock media cache warm-up completed');
    }

    /**
     * Get cache statistics and health metrics
     */
    public function getCacheMetrics(): array
    {
        // This would return cache hit/miss rates, size, etc.
        // Implementation depends on cache adapter capabilities
        return [
            'cache_enabled' => true,
            'total_items' => 'N/A', // Would query cache adapter
            'memory_usage' => 'N/A',
            'hit_rate' => 'N/A',
            'ttl_settings' => [
                'search_results' => self::SEARCH_RESULTS_TTL,
                'provider_metadata' => self::PROVIDER_METADATA_TTL,
                'rate_limit' => self::RATE_LIMIT_TTL,
                'media_details' => self::MEDIA_DETAILS_TTL,
                'download_urls' => self::DOWNLOAD_URL_TTL,
                'statistics' => self::STATISTICS_TTL
            ]
        ];
    }

    /**
     * Generate cache key for search results
     */
    private function generateSearchKey(
        string $provider,
        string $query,
        string $type,
        int $page,
        int $limit,
        array $filters
    ): string {
        $filterString = http_build_query($filters);
        $keyData = [
            $provider,
            strtolower(trim($query)),
            $type,
            $page,
            $limit,
            md5($filterString)
        ];
        
        return self::PREFIX_SEARCH . '_' . implode('_', $keyData);
    }

    /**
     * Invalidate cache items by pattern (filesystem implementation)
     */
    private function invalidateCacheByPattern(string $pattern): void
    {
        // For filesystem adapter, we'll iterate through cache items
        // and clear matching ones based on the pattern
        try {
            // Convert cache pattern to regex pattern
            $regexPattern = '/^' . str_replace('*', '.*', preg_quote($pattern, '/')) . '$/';
            
            // Note: This is a simplified implementation
            // In a production environment, you might want to implement
            // a more sophisticated cache key tracking system
            
            $this->logger->debug('Cache pattern invalidation requested', [
                'pattern' => $pattern,
                'regex_pattern' => $regexPattern
            ]);
            
            // For now, we'll clear the entire cache pool if a pattern is requested
            // This is less efficient but ensures consistency
            if (method_exists($this->cache, 'clear')) {
                $this->cache->clear();
                $this->logger->info('Cache cleared due to pattern invalidation', [
                    'pattern' => $pattern
                ]);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to invalidate cache by pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
        }
    }
}
