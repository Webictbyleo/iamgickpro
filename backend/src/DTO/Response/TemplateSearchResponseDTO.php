<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Response DTO for template search results
 * 
 * Provides specific typing for template search operations to ensure the frontend API
 * knows exactly what structure to expect from template searches.
 */
readonly class TemplateSearchResponseDTO
{
    /**
     * @param array<int, array{
     *     id: int,
     *     uuid: string,
     *     name: string,
     *     description: string,
     *     category: string,
     *     tags: array<int, string>,
     *     thumbnailUrl: string,
     *     previewUrl: string,
     *     width: int,
     *     height: int,
     *     isPremium: bool,
     *     isActive: bool,
     *     rating: float,
     *     ratingCount: int,
     *     usageCount: int,
     *     createdAt: string,
     *     updatedAt: string
     * }> $templates Array of template data with specific structure
     * @param int $page Current page number
     * @param int $limit Items per page
     * @param int $total Total number of templates found
     * @param int $totalPages Total number of pages
     * @param string $message Response message
     */
    public function __construct(
        public array $templates,
        public int $page,
        public int $limit,
        public int $total,
        public int $totalPages,
        public string $message = 'Template search completed successfully',
    ) {}

    /**
     * Convert the DTO to array format for JSON response
     *
     * @return array{
     *     templates: array<int, array{
     *         id: int,
     *         uuid: string,
     *         name: string,
     *         description: string,
     *         category: string,
     *         tags: array<int, string>,
     *         thumbnailUrl: string,
     *         previewUrl: string,
     *         width: int,
     *         height: int,
     *         isPremium: bool,
     *         isActive: bool,
     *         rating: float,
     *         ratingCount: int,
     *         usageCount: int,
     *         createdAt: string,
     *         updatedAt: string
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
            'templates' => $this->templates,
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
     * Create template search response from individual parameters
     *
     * @param array<int, array{
     *     id: int,
     *     uuid: string,
     *     name: string,
     *     description: string,
     *     category: string,
     *     tags: array<int, string>,
     *     thumbnailUrl: string,
     *     previewUrl: string,
     *     width: int,
     *     height: int,
     *     isPremium: bool,
     *     isActive: bool,
     *     rating: float,
     *     ratingCount: int,
     *     usageCount: int,
     *     createdAt: string,
     *     updatedAt: string
     * }> $templates Array of template data with specific structure
     * @param int $page Current page number
     * @param int $limit Items per page
     * @param int $total Total number of templates found
     * @param string $message Response message
     * @return self
     */
    public static function create(
        array $templates,
        int $page,
        int $limit,
        int $total,
        string $message = 'Template search completed successfully'
    ): self {
        $totalPages = (int) ceil((float) $total / (float) $limit);
        
        return new self(
            templates: $templates,
            page: $page,
            limit: $limit,
            total: $total,
            totalPages: $totalPages,
            message: $message
        );
    }
}
