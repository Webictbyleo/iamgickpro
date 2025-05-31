<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class DuplicateProjectRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Project name is required')]
        #[Assert\Length(
            min: 1,
            max: 255,
            minMessage: 'Project name must be at least {{ limit }} characters long',
            maxMessage: 'Project name cannot be longer than {{ limit }} characters'
        )]
        public ?string $name = null,
    ) {
    }
}
