<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SearchTemplateRequestDTO
{
    public function __construct(
        #[Assert\Type(type: 'string', message: 'Query must be a string')]
        public ?string $q = '',

        #[Assert\Choice(choices: ['social-media', 'presentation', 'print', 'marketing', 'document', 'logo', 'web-graphics', 'video', 'animation'], message: 'Invalid category')]
        public ?string $category = null,

        #[Assert\Type(type: 'integer', message: 'Page must be an integer')]
        #[Assert\Positive(message: 'Page must be positive')]
        public int $page = 1,

        #[Assert\Type(type: 'integer', message: 'Limit must be an integer')]
        #[Assert\Range(min: 1, max: 50, notInRangeMessage: 'Limit must be between 1 and 50')]
        public int $limit = 20,
    ) {
    }
}
