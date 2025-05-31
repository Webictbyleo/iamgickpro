<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class StockSearchRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Query is required for stock search')]
        #[Assert\Length(
            min: 1,
            max: 255,
            minMessage: 'Query must be at least {{ limit }} character long',
            maxMessage: 'Query cannot be longer than {{ limit }} characters'
        )]
        public string $query,

        #[Assert\Range(
            min: 1,
            notInRangeMessage: 'Page must be {{ min }} or greater'
        )]
        public int $page = 1,

        #[Assert\Range(
            min: 1,
            max: 50,
            notInRangeMessage: 'Limit must be between {{ min }} and {{ max }}'
        )]
        public int $limit = 20,

        #[Assert\Choice(
            choices: ['image', 'video'],
            message: 'Type must be one of: image, video'
        )]
        public string $type = 'image',

        #[Assert\Choice(
            choices: ['unsplash', 'pexels', 'pixabay'],
            message: 'Source must be one of: unsplash, pexels, pixabay'
        )]
        public string $source = 'unsplash',
    ) {
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}
