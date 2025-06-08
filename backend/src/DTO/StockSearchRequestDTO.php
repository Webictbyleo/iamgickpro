<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for stock media search requests.
 * 
 * Handles search operations for stock media from external providers
 * like Unsplash, Pexels, and Pixabay. Provides structured search
 * parameters for integrating with third-party stock media APIs
 * to expand the available media library for users.
 */
readonly class StockSearchRequestDTO
{
    public function __construct(
        /**
         * Search query for stock media.
         * 
         * The search term to find relevant stock photos and videos
         * from external providers. Must be descriptive enough to
         * return meaningful results from stock media APIs.
         */
        #[Assert\NotBlank(message: 'Query is required for stock search')]
        #[Assert\Length(
            min: 1,
            max: 255,
            minMessage: 'Query must be at least {{ limit }} character long',
            maxMessage: 'Query cannot be longer than {{ limit }} characters'
        )]
        public string $query,

        /**
         * Page number for stock media results pagination.
         * 
         * Specifies which page of stock media results to return
         * from the external provider's API. Used to implement
         * pagination for large result sets.
         */
        #[Assert\Range(
            min: 1,
            notInRangeMessage: 'Page must be {{ min }} or greater'
        )]
        public int $page = 1,

        /**
         * Number of stock media items per page.
         * 
         * Controls how many stock media items are requested from
         * the external provider. Limited to prevent API rate
         * limiting and maintain performance.
         */
        #[Assert\Range(
            min: 1,
            max: 50,
            notInRangeMessage: 'Limit must be between {{ min }} and {{ max }}'
        )]
        public int $limit = 20,

        /**
         * Type of stock media to search for.
         * 
         * Specifies whether to search for images or videos from
         * the stock media provider. Defaults to images as they
         * are more commonly used in designs.
         */
        #[Assert\Choice(
            choices: ['image', 'video','shape', 'icon'],
            message: 'Type must be one of: image, video'
        )]
        public string $type = 'image',
        
        
        
    ) {
    }

    /**
     * Calculate pagination offset for stock media API.
     * 
     * Calculates the offset parameter needed for stock media
     * provider APIs based on the current page and limit values.
     * 
     * @return int Offset for stock media API pagination
     */
    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}
