<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Response DTO for search suggestions
 * 
 * Provides specific typing for search suggestion operations to ensure the frontend API
 * knows exactly what structure to expect from autocomplete suggestions. Returns an array
 * of suggestion objects with text and type information for UI display.
 */
readonly class SearchSuggestionResponseDTO
{
    /**
     * @param array<int, array{
     *     text: string,
     *     type: string
     * }> $suggestions Array of suggestion data with specific structure
     * @param string $query The original search query that generated these suggestions
     * @param string $message Response message
     */
    public function __construct(
        public array $suggestions,
        public string $query = '',
        public string $message = 'Search suggestions retrieved successfully',
    ) {}

    /**
     * Convert the DTO to array format for JSON response
     *
     * @return array{
     *     suggestions: array<int, array{
     *         text: string,
     *         type: string
     *     }>,
     *     query: string,
     *     message: string
     * }
     */
    public function toArray(): array
    {
        return [
            'suggestions' => $this->suggestions,
            'query' => $this->query,
            'message' => $this->message,
        ];
    }

    /**
     * Create search suggestions response from individual parameters
     *
     * @param array<int, array{
     *     text: string,
     *     type: string
     * }> $suggestions Array of suggestion data with specific structure
     * @param string $query The original search query that generated these suggestions
     * @param string $message Response message
     * @return self
     */
    public static function create(
        array $suggestions,
        string $query = '',
        string $message = 'Search suggestions retrieved successfully'
    ): self {
        return new self(
            suggestions: $suggestions,
            query: $query,
            message: $message
        );
    }
}
