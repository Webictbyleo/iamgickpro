<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for general search requests.
 * 
 * Handles search operations across various entities in the platform
 * including designs, projects, templates, and media. Provides
 * standardized pagination and search term validation.
 */
class SearchRequestDTO
{
    public function __construct(
        /**
         * Search query term.
         * 
         * The text to search for across entity names, descriptions,
         * and other searchable fields. Must be at least 1 character
         * and no longer than 255 characters to prevent abuse.
         */
        #[Assert\NotBlank(message: 'Search query is required')]
        #[Assert\Length(min: 1, max: 255, minMessage: 'Search query must be at least 1 character', maxMessage: 'Search query cannot exceed 255 characters')]
        public readonly string $query,

        /**
         * Page number for pagination.
         * 
         * Specifies which page of results to return. Must be a positive
         * integer starting from 1. Used for implementing pagination
         * in search results.
         */
        #[Assert\Type('integer', message: 'Page must be an integer')]
        #[Assert\Positive(message: 'Page must be positive')]
        public readonly int $page = 1,

        /**
         * Number of items per page.
         * 
         * Controls how many search results are returned per page.
         * Limited to a maximum of 50 items to maintain performance
         * and prevent excessive data transfer.
         */
        #[Assert\Type('integer', message: 'Limit must be an integer')]
        #[Assert\Range(min: 1, max: 50, notInRangeMessage: 'Limit must be between 1 and 50')]
        public readonly int $limit = 20,
    ) {}
}
