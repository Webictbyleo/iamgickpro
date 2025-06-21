<?php

declare(strict_types=1);

namespace App\Service\StockMedia;

use App\Entity\Shape;
use App\Repository\ShapeRepository;
use Psr\Log\LoggerInterface;

/**
 * Shape Stock Media Service
 * 
 * Provides shape search functionality for the stock media system.
 * Searches through locally stored SVG shapes with metadata.
 */
class ShapeService implements StockMediaServiceInterface
{
    public function __construct(
        private readonly ShapeRepository $shapeRepository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Search for shapes based on query parameters
     * 
     * @param string $query Search term
     * @param int $page Page number
     * @param int $limit Items per page
     * @param array $filters Additional filters (category, shapeCategory)
     * @return array{
     *     items: array,
     *     total: int,
     *     page: int,
     *     limit: int,
     *     hasMore: bool
     * }
     */
    public function search(string $query, int $page = 1, int $limit = 20, array $filters = []): array
    {
        $this->logger->info('Searching shapes', [
            'query' => $query,
            'filters' => $filters,
            'page' => $page,
            'limit' => $limit
        ]);

        // For comprehensive search, we'll include category and shape_category filters in the query itself
        // This allows users to search by typing category names like "arrows", "basic", etc.
        $searchQuery = $query;
        
        // If specific filters are provided, we can enhance the search
        if (!empty($filters['category'])) {
            $searchQuery = empty($searchQuery) ? $filters['category'] : $searchQuery . ' ' . $filters['category'];
        }
        
        if (!empty($filters['shapeCategory']) || !empty($filters['shape_category'])) {
            $shapeCategory = $filters['shapeCategory'] ?? $filters['shape_category'];
            $searchQuery = empty($searchQuery) ? $shapeCategory : $searchQuery . ' ' . $shapeCategory;
        }

        // Search shapes using the simplified repository method
        $shapes = $this->shapeRepository->findBySearch($searchQuery, $page, $limit);
        $total = $this->shapeRepository->countBySearch($searchQuery);

        // Convert to stock media format
        $items = array_map(fn(Shape $shape) => $shape->toStockMediaFormat(), $shapes);

        $hasMore = ($page * $limit) < $total;

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'hasMore' => $hasMore
        ];
    }

    /**
     * Get popular/featured shapes
     * 
     * @param int $limit Number of shapes to return
     * @return array
     */
    public function getFeatured(int $limit = 20): array
    {
        $this->logger->info('Getting featured shapes', ['limit' => $limit]);

        $shapes = $this->shapeRepository->getPopularShapes($limit);
        
        return array_map(fn(Shape $shape) => $shape->toStockMediaFormat(), $shapes);
    }

    /**
     * Get shapes by category
     * 
     * @param string $category Category name
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array
     */
    public function getByCategory(string $category, int $page = 1, int $limit = 20): array
    {
        $this->logger->info('Getting shapes by category', [
            'category' => $category,
            'page' => $page,
            'limit' => $limit
        ]);

        $shapes = $this->shapeRepository->findByCategory($category, $page, $limit);
        $total = $this->shapeRepository->countByCategory($category);

        $items = array_map(fn(Shape $shape) => $shape->toStockMediaFormat(), $shapes);
        $hasMore = ($page * $limit) < $total;

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'hasMore' => $hasMore
        ];
    }

    /**
     * Get available categories
     * 
     * @return string[]
     */
    public function getCategories(): array
    {
        return $this->shapeRepository->getCategories();
    }

    /**
     * Get available shape categories
     * 
     * @return string[]
     */
    public function getShapeCategories(): array
    {
        return $this->shapeRepository->getShapeCategories();
    }

    /**
     * Download/get shape by ID - returns the shape URL
     * 
     * @param string $mediaId Shape ID
     * @param string $quality Quality parameter (ignored for SVG)
     * @return string|null Shape URL or null if not found
     */
    public function downloadMedia(string $mediaId, string $quality = 'regular'): ?string
    {
        // Parse shape ID (format: "shape_123")
        if (!str_starts_with($mediaId, 'shape_')) {
            return null;
        }

        $shapeId = (int) substr($mediaId, 6);
        $shape = $this->shapeRepository->find($shapeId);

        if (!$shape) {
            return null;
        }

        return $shape->getUrl();
    }

    /**
     * Download/get shape by ID
     * 
     * @param string $id Shape ID
     * @return array|null Shape data or null if not found
     */
    public function download(string $id): ?array
    {
        // Parse shape ID (format: "shape_123")
        if (!str_starts_with($id, 'shape_')) {
            return null;
        }

        $shapeId = (int) substr($id, 6);
        $shape = $this->shapeRepository->find($shapeId);

        if (!$shape) {
            return null;
        }

        return $shape->toStockMediaFormat();
    }

    /**
     * Check if this service supports the given media type
     * 
     * @param string $type Media type to check
     * @return bool
     */
    public function supportsType(string $type): bool
    {
        return $type === 'shape';
    }

    /**
     * Get service name for identification
     * 
     * @return string
     */
    public function getName(): string
    {
        return 'shapes';
    }

    /**
     * Get supported media types
     * 
     * @return string[]
     */
    public function getSupportedTypes(): array
    {
        return ['shape'];
    }

    /**
     * Get service statistics
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        return $this->shapeRepository->getStatistics();
    }

    /**
     * Search suggestions based on partial query
     * 
     * @param string $partial Partial search term
     * @param int $limit Number of suggestions
     * @return string[]
     */
    public function getSuggestions(string $partial, int $limit = 10): array
    {
        if (strlen($partial) < 2) {
            return [];
        }

        // Get suggestions from keywords and categories
        $suggestions = [];
        
        // Get categories that match
        $categories = $this->shapeRepository->getCategories();
        foreach ($categories as $category) {
            if (stripos($category, $partial) !== false) {
                $suggestions[] = $category;
            }
        }

        // Get shape categories that match
        $shapeCategories = $this->shapeRepository->getShapeCategories();
        foreach ($shapeCategories as $shapeCategory) {
            if (stripos($shapeCategory, $partial) !== false) {
                $suggestions[] = $shapeCategory;
            }
        }

        // Remove duplicates and limit results
        $suggestions = array_unique($suggestions);
        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Validate if service is properly configured
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        // Check if there are any shapes in the database
        $stats = $this->getStatistics();
        return $stats['total'] > 0;
    }

    /**
     * Get configuration information
     * 
     * @return array
     */
    public function getConfigInfo(): array
    {
        $stats = $this->getStatistics();
        
        return [
            'service' => 'Shape Service',
            'configured' => $this->isConfigured(),
            'total_shapes' => $stats['total'],
            'categories' => $stats['categories'],
            'average_size' => $stats['averageSize'] ?? 0,
            'supported_types' => $this->getSupportedTypes()
        ];
    }
}
