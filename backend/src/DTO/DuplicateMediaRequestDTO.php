<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class DuplicateMediaRequestDTO
{
    public function __construct(
        #[Assert\Length(
            max: 255,
            maxMessage: 'Media name cannot be longer than {{ limit }} characters'
        )]
        public ?string $name = null,
    ) {
    }
}
