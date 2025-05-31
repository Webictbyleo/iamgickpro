<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class SearchProjectsRequestDTO
{
    public function __construct(
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

        #[Assert\Length(
            max: 255,
            maxMessage: 'Search query cannot be longer than {{ limit }} characters'
        )]
        public ?string $search = null,

        public ?string $tags = null,
    ) {
    }

    public function getTagsArray(): ?array
    {
        if ($this->tags === null || $this->tags === '') {
            return null;
        }

        return array_filter(array_map('trim', explode(',', $this->tags)));
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}
