<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Response DTO for media search results
 * 
 * Provides specific typing for media search operations to ensure the frontend API
 * knows exactly what structure to expect from media searches. Includes pagination
 * metadata and media-specific data fields like file types, sizes, and URLs.
 */
readonly class MediaSearchResponseDTO
{
    /**
     * @param array<int, array{
     *     id: int,
     *     name: string,
     *     type: string,
     *     mime_type: string,
     *     size: int,
     *     url: string,
     *     thumbnail_url: string|null,
     *     tags: array|null,
     *     created_at: string|null,
     *     type: string
     * }> $media Array of media data with specific structure
     * @param int $page Current page number
     * @param int $limit Items per page
     * @param int $total Total number of media items found
     * @param int $totalPages Total number of pages
     * @param string $message Response message
     */
    public function __construct(
        public array $media,
        public int $page,
        public int $limit,
        public int $total,
        public int $totalPages,
        public string $message = 'Media search completed successfully',
    ) {}

    /**
     * Convert the DTO to array format for JSON response
     *
     * @return array{
     *     media: array<int, array{
     *         id: int,
     *         name: string,
     *         type: string,
     *         mime_type: string,
     *         size: int,
     *         url: string,
     *         thumbnail_url: string|null,
     *         tags: array|null,
     *         created_at: string|null,
     *         type: string
     *     }>,
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
            'media' => $this->media,
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
     * Create media search response from individual parameters
     *
     * @param array<int, array{
     *     id: int,
     *     name: string,
     *     type: string,
     *     mime_type: string,
     *     size: int,
     *     url: string,
     *     thumbnail_url: string|null,
     *     tags: array|null,
     *     created_at: string|null,
     *     type: string
     * }> $media Array of media data with specific structure
     * @param int $page Current page number
     * @param int $limit Items per page
     * @param int $total Total number of media items found
     * @param string $message Response message
     * @return self
     */
    public static function create(
        array $media,
        int $page,
        int $limit,
        int $total,
        string $message = 'Media search completed successfully'
    ): self {
        $totalPages = (int) ceil((float) $total / (float) $limit);
        
        return new self(
            media: $media,
            page: $page,
            limit: $limit,
            total: $total,
            totalPages: $totalPages,
            message: $message
        );
    }
}
