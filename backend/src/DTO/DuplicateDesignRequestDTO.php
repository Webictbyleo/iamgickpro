<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class DuplicateDesignRequestDTO
{
    public function __construct(
        #[Assert\Length(min: 1, max: 255, minMessage: 'Design name must be at least 1 character', maxMessage: 'Design name cannot exceed 255 characters')]
        public readonly ?string $name = null,

        #[Assert\Type('integer', message: 'Project ID must be an integer')]
        #[Assert\Positive(message: 'Project ID must be positive')]
        public readonly ?int $projectId = null,
    ) {}
}
