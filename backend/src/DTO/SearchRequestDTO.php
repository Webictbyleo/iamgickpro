<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class SearchRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Search query is required')]
        #[Assert\Length(min: 1, max: 255, minMessage: 'Search query must be at least 1 character', maxMessage: 'Search query cannot exceed 255 characters')]
        public readonly string $query,

        #[Assert\Type('integer', message: 'Page must be an integer')]
        #[Assert\Positive(message: 'Page must be positive')]
        public readonly int $page = 1,

        #[Assert\Type('integer', message: 'Limit must be an integer')]
        #[Assert\Range(min: 1, max: 50, notInRangeMessage: 'Limit must be between 1 and 50')]
        public readonly int $limit = 20,
    ) {}
}
