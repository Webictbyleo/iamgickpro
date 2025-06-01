<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for media search and filtering requests.
 * 
 * Handles search operations for media files with advanced filtering
 * capabilities including type, source, pagination, and tag-based
 * filtering. Used by the media library to provide rich search
 * functionality for users to find specific media files.
 */
readonly class SearchMediaRequestDTO
{
    public function __construct(
        /**
         * Page number for pagination.
         * 
         * Specifies which page of media results to return.
         * Must be 1 or greater for valid pagination.
         */
        #[Assert\Range(
            min: 1,
            notInRangeMessage: 'Page must be {{ min }} or greater'
        )]
        public int $page = 1,

        /**
         * Number of media items per page.
         * 
         * Controls how many media files are returned per page.
         * Limited to a maximum of 50 to maintain performance.
         */
        #[Assert\Range(
            min: 1,
            max: 50,
            notInRangeMessage: 'Limit must be between {{ min }} and {{ max }}'
        )]
        public int $limit = 20,

        /**
         * Media type filter.
         * 
         * Filters media by type (image, video, or audio).
         * When null, all media types are included in results.
         */
        #[Assert\Choice(
            choices: ['image', 'video', 'audio'],
            message: 'Type must be one of: image, video, audio'
        )]
        public ?string $type = null,

        /**
         * Media source filter.
         * 
         * Filters media by its source origin (upload for user uploads,
         * or stock photo providers like unsplash, pexels, pixabay).
         * When null, all sources are included.
         */
        #[Assert\Choice(
            choices: ['upload', 'unsplash', 'pexels', 'pixabay'],
            message: 'Source must be one of: upload, unsplash, pexels, pixabay'
        )]
        public ?string $source = null,

        /**
         * Search query term.
         * 
         * Text to search for in media file names, descriptions,
         * and metadata. When null, no text-based filtering is applied.
         */
        #[Assert\Length(
            max: 255,
            maxMessage: 'Search query cannot be longer than {{ limit }} characters'
        )]
        public ?string $search = null,

        /**
         * Comma-separated list of tags for filtering.
         * 
         * Tags to filter media by. Multiple tags can be specified
         * separated by commas. The system will find media that
         * matches any of the specified tags.
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

    /**
     * Get filters as associative array.
     * 
     * Returns all non-null filters as an associative array
     * for use in repository query methods.
     * 
     * @return array<string, string> Filter array for database queries
     */
    public function getFilters(): array
    {
        $filters = [];
        
        if ($this->type !== null) {
            $filters['type'] = $this->type;
        }
        
        if ($this->source !== null) {
            $filters['source'] = $this->source;
        }
        
        return $filters;
    }
}
