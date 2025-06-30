<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

/**
 * Rate limiting service for API endpoints
 */
readonly class RateLimitService
{
    public function __construct(
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Check if action is rate limited for a user
     * 
     * @param string $action The action being performed (e.g., 'data_download', 'data_export')
     * @param int $userId The user ID
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $windowSeconds Time window in seconds
     * @return bool True if rate limited, false if allowed
     */
    public function isRateLimited(string $action, int $userId, int $maxAttempts = 5, int $windowSeconds = 3600): bool
    {
        $cacheKey = sprintf('rate_limit_%s_%d', $action, $userId);
        
        try {
            $attempts = $this->cache->get($cacheKey, function (ItemInterface $item) use ($windowSeconds): int {
                $item->expiresAfter($windowSeconds);
                return 0;
            });
            
            return $attempts >= $maxAttempts;
        } catch (\Exception $e) {
            $this->logger->error('Rate limit check failed', [
                'action' => $action,
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            
            // Fail open - allow the action if cache is unavailable
            return false;
        }
    }

    /**
     * Record an attempt for rate limiting
     * 
     * @param string $action The action being performed
     * @param int $userId The user ID
     * @param int $windowSeconds Time window in seconds
     */
    public function recordAttempt(string $action, int $userId, int $windowSeconds = 3600): void
    {
        $cacheKey = sprintf('rate_limit_%s_%d', $action, $userId);
        
        try {
            $attempts = $this->cache->get($cacheKey, function (ItemInterface $item) use ($windowSeconds): int {
                $item->expiresAfter($windowSeconds);
                return 0;
            });
            
            $this->cache->delete($cacheKey);
            $this->cache->get($cacheKey, function (ItemInterface $item) use ($windowSeconds, $attempts): int {
                $item->expiresAfter($windowSeconds);
                return $attempts + 1;
            });
        } catch (\Exception $e) {
            $this->logger->error('Failed to record rate limit attempt', [
                'action' => $action,
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get remaining attempts for a user action
     * 
     * @param string $action The action being performed
     * @param int $userId The user ID
     * @param int $maxAttempts Maximum attempts allowed
     * @return int Remaining attempts
     */
    public function getRemainingAttempts(string $action, int $userId, int $maxAttempts = 5): int
    {
        $cacheKey = sprintf('rate_limit_%s_%d', $action, $userId);
        
        try {
            $attempts = $this->cache->get($cacheKey, function (ItemInterface $item): int {
                return 0;
            });
            
            return max(0, $maxAttempts - $attempts);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get remaining attempts', [
                'action' => $action,
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return $maxAttempts;
        }
    }
}
