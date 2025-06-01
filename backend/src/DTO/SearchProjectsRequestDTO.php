<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for project search requests.
 * 
 * Handles search and filtering operations for user projects
 * with support for text-based search, tag filtering, and
 * pagination. Used by the project management system to allow
 * users to find and organize their design projects efficiently.
 */
readonly class SearchProjectsRequestDTO
{
    public function __construct(
        /**
         * Page number for pagination.
         * 
         * Specifies which page of project results to return.
         * Must be 1 or greater for valid pagination navigation.
         */
        #[Assert\Range(
            min: 1,
            notInRangeMessage: 'Page must be {{ min }} or greater'
        )]
        public int $page = 1,

        /**
         * Number of projects per page.
         * 
         * Controls how many projects are returned per page.
         * Limited to a maximum of 50 to maintain performance
         * and reasonable response times.
         */
        #[Assert\Range(
            min: 1,
            max: 50,
            notInRangeMessage: 'Limit must be between {{ min }} and {{ max }}'
        )]
        public int $limit = 20,

        /**
         * Search query for project names and descriptions.
         * 
         * Text to search for in project names, descriptions,
         * and other searchable fields. When null, no text-based
         * filtering is applied to the results.
         */
        #[Assert\Length(
            max: 255,
            maxMessage: 'Search query cannot be longer than {{ limit }} characters'
        )]
        public ?string $search = null,

        /**
         * Comma-separated list of tags for filtering.
         * 
         * Tags to filter projects by. Multiple tags can be specified
         * separated by commas. The system will find projects that
         * match any of the specified tags.
         */
        public ?string $tags = null,
    ) {
    }

    /**
     * Convert comma-separated tags string to array.
     * 
     * Parses the tags string and returns an array of individual
     * tag names, trimming whitespace and filtering out empty values.
     * 
     * @return string[]|null Array of tag names or null if no tags
     */
    public function getTagsArray(): ?array
    {
        if ($this->tags === null || $this->tags === '') {
            return null;
        }

        return array_filter(array_map('trim', explode(',', $this->tags)));
    }

    /**
     * Calculate pagination offset.
     * 
     * Calculates the database offset for pagination based on
     * the current page and limit values.
     * 
     * @return int Database offset for pagination
     */
    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}
