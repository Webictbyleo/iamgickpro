<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Response DTO for project search results
 * 
 * Provides specific typing for project search operations to ensure the frontend API
 * knows exactly what structure to expect from project searches. Includes pagination
 * metadata and project-specific data fields.
 */
readonly class ProjectSearchResponseDTO
{
    /**
     * @param array<int, array{
     *     id: int,
     *     name: string,
     *     description: string|null,
     *     thumbnail: string|null,
     *     updatedAt: string,
     *     type: string
     * }> $projects Array of project data with specific structure
     * @param int $page Current page number
     * @param int $limit Items per page
     * @param int $total Total number of projects found
     * @param int $totalPages Total number of pages
     * @param string $message Response message
     */
    public function __construct(
        public array $projects,
        public int $page,
        public int $limit,
        public int $total,
        public int $totalPages,
        public string $message = 'Project search completed successfully',
    ) {}

    /**
     * Convert the DTO to array format for JSON response
     *
     * @return array{
     *     projects: array<int, array{
     *         id: int,
     *         name: string,
     *         description: string|null,
     *         thumbnail: string|null,
     *         updatedAt: string,
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
            'projects' => $this->projects,
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
     * Create project search response from individual parameters
     *
     * @param array<int, array{
     *     id: int,
     *     name: string,
     *     description: string|null,
     *     thumbnail: string|null,
     *     updatedAt: string,
     *     type: string
     * }> $projects Array of project data with specific structure
     * @param int $page Current page number
     * @param int $limit Items per page
     * @param int $total Total number of projects found
     * @param string $message Response message
     * @return self
     */
    public static function create(
        array $projects,
        int $page,
        int $limit,
        int $total,
        string $message = 'Project search completed successfully'
    ): self {
        $totalPages = (int) ceil((float) $total / (float) $limit);
        
        return new self(
            projects: $projects,
            page: $page,
            limit: $limit,
            total: $total,
            totalPages: $totalPages,
            message: $message
        );
    }
}
