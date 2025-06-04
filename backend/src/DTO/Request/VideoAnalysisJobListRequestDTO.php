<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for video analysis job listing requests.
 * 
 * Handles parameters for filtering and paginating video
 * analysis jobs in the user's job history.
 */
final readonly class VideoAnalysisJobListRequestDTO
{
    public function __construct(
        /**
         * Page number for pagination.
         * 
         * Page number starting from 1. Used to paginate
         * through the user's video analysis job history.
         */
        #[Assert\Type(type: 'integer', message: 'Page must be an integer')]
        #[Assert\Positive(message: 'Page must be positive')]
        public int $page = 1,

        /**
         * Number of jobs per page.
         * 
         * Controls how many video analysis jobs are returned
         * per page. Maximum of 50 jobs per page.
         */
        #[Assert\Type(type: 'integer', message: 'Limit must be an integer')]
        #[Assert\Range(min: 1, max: 50, notInRangeMessage: 'Limit must be between 1 and 50')]
        public int $limit = 10,

        /**
         * Filter by job status.
         * 
         * Optional status filter to show only jobs in specific states:
         * - 'processing': Currently running jobs
         * - 'completed': Successfully finished jobs
         * - 'failed': Jobs that encountered errors
         */
        #[Assert\Choice(
            choices: ['processing', 'completed', 'failed'],
            message: 'Invalid status. Must be processing, completed, or failed'
        )]
        public ?string $status = null,

        /**
         * Sort order for the results.
         * 
         * Determines the ordering of results:
         * - 'newest': Most recent jobs first (default)
         * - 'oldest': Oldest jobs first
         * - 'status': Group by status
         */
        #[Assert\Choice(
            choices: ['newest', 'oldest', 'status'],
            message: 'Invalid sort. Must be newest, oldest, or status'
        )]
        public string $sort = 'newest',
    ) {
    }
}
