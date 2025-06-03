<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Response DTO for global search results (search across multiple content types)
 * 
 * Provides specific typing for global search operations that combine results from projects,
 * templates, and media. Returns mixed results with pagination metadata and query information.
 * Each item includes a type field to distinguish between different content types.
 */
readonly class GlobalSearchResponseDTO
{
    /**
     * @param array<int, array{
     *     id: int,
     *     name: string,
     *     description?: string|null,
     *     thumbnail?: string|null,
     *     thumbnail_url?: string|null,
     *     category?: string,
     *     tags?: array|null,
     *     is_premium?: bool,
     *     mime_type?: string,
     *     size?: int,
     *     url?: string,
     *     created_at?: string|null,
     *     updatedAt?: string,
     *     type: string
     * }> $results Array of mixed search results with specific structure
     * @param string $query The search query that generated these results
     * @param int $page Current page number
     * @param int $limit Items per page
     * @param int $total Total number of results found across all types
     * @param int $totalPages Total number of pages
     * @param string $message Response message
     */
    public function __construct(
        public array $results,
        public string $query,
        public int $page,
        public int $limit,
        public int $total,
        public int $totalPages,
        public string $message = 'Global search completed successfully',
    ) {}

    /**
     * Convert the DTO to array format for JSON response
     *
     * @return array{
     *     results: array<int, array{
     *         id: int,
     *         name: string,
     *         description?: string|null,
     *         thumbnail?: string|null,
     *         thumbnail_url?: string|null,
     *         category?: string,
     *         tags?: array|null,
     *         is_premium?: bool,
     *         mime_type?: string,
     *         size?: int,
     *         url?: string,
     *         created_at?: string|null,
     *         updatedAt?: string,
     *         type: string
     *     }>,
     *     query: string,
     *     pagination: array{
     *         page: int,
     *         limit: int,
     *         total: int,
     *         totalPages: int
     *     },
     *     message: string
     * }
     */
    public function toArray(): array
    {
        return [
            'results' => $this->results,
            'query' => $this->query,
            'pagination' => [
                'page' => $this->page,
                'limit' => $this->limit,
                'total' => $this->total,
                'totalPages' => $this->totalPages,
            ],
            'message' => $this->message,
        ];
    }

    /**
     * Create global search response from individual parameters
     *
     * @param array<int, array{
     *     id: int,
     *     name: string,
     *     description?: string|null,
     *     thumbnail?: string|null,
     *     thumbnail_url?: string|null,
     *     category?: string,
     *     tags?: array|null,
     *     is_premium?: bool,
     *     mime_type?: string,
     *     size?: int,
     *     url?: string,
     *     created_at?: string|null,
     *     updatedAt?: string,
     *     type: string
     * }> $results Array of mixed search results with specific structure
     * @param string $query The search query that generated these results
     * @param int $page Current page number
     * @param int $limit Items per page
     * @param int $total Total number of results found across all types
     * @param string $message Response message
     * @return self
     */
    public static function create(
        array $results,
        string $query,
        int $page,
        int $limit,
        int $total,
        string $message = 'Global search completed successfully'
    ): self {
        $totalPages = (int) ceil((float) $total / (float) $limit);
        
        return new self(
            results: $results,
            query: $query,
            page: $page,
            limit: $limit,
            total: $total,
            totalPages: $totalPages,
            message: $message
        );
    }
}
