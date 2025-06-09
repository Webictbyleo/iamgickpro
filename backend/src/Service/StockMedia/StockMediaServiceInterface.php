<?php

declare(strict_types=1);

namespace App\Service\StockMedia;

/**
 * Interface for stock media service providers.
 * 
 * Defines the contract for integrating with external stock media APIs
 * like Unsplash, Pexels, Iconfinder, etc.
 */
interface StockMediaServiceInterface
{
    /**
     * Search for stock media with the given query and filters
     * 
     * @param string $query Search term
     * @param int $page Page number for pagination
     * @param int $limit Number of items per page
     * @param array $filters Additional filters (orientation, color, etc.)
     * @return array{
     *     items: array,
     *     total: int,
     *     page: int,
     *     limit: int,
     *     hasMore: bool
     * }
     * @throws StockMediaException
     */
    public function search(string $query, int $page = 1, int $limit = 20, array $filters = []): array;

    /**
     * Get supported media types for this service
     * 
     * @return string[] Array of supported types (image, video, icon, etc.)
     */
    public function getSupportedTypes(): array;

    /**
     * Check if this service supports the given media type
     * 
     * @param string $type Media type to check
     * @return bool
     */
    public function supportsType(string $type): bool;

    /**
     * Get service name for identification
     * 
     * @return string Service identifier
     */
    public function getName(): string;

    /**
     * Download media from the service (optional)
     * 
     * @param string $mediaId Media identifier
     * @param string $quality Quality/size variant
     * @return string|null Download URL or null if not supported
     */
    public function downloadMedia(string $mediaId, string $quality = 'regular'): ?string;
}
