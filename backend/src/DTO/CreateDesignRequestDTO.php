<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateDesignRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Design name is required')]
        #[Assert\Length(min: 1, max: 255, minMessage: 'Design name must be at least 1 character', maxMessage: 'Design name cannot exceed 255 characters')]
        public readonly string $name,

        #[Assert\Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')]
        public readonly ?string $description = null,

        #[Assert\Type('array', message: 'Design data must be an array')]
        public readonly array $data = [],

        #[Assert\Type('integer', message: 'Project ID must be an integer')]
        #[Assert\Positive(message: 'Project ID must be positive')]
        public readonly ?int $projectId = null,

        #[Assert\Type('integer', message: 'Width must be an integer')]
        #[Assert\Positive(message: 'Width must be positive')]
        #[Assert\Range(min: 1, max: 10000, notInRangeMessage: 'Width must be between 1 and 10000 pixels')]
        public readonly int $width = 1920,

        #[Assert\Type('integer', message: 'Height must be an integer')]
        #[Assert\Positive(message: 'Height must be positive')]
        #[Assert\Range(min: 1, max: 10000, notInRangeMessage: 'Height must be between 1 and 10000 pixels')]
        public readonly int $height = 1080,

        #[Assert\Type('bool', message: 'Is public must be a boolean')]
        public readonly bool $isPublic = false,
    ) {}

    public function hasProjectId(): bool
    {
        return $this->projectId !== null;
    }
}
