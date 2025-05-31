<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateDesignRequestDTO
{
    public function __construct(
        #[Assert\Length(min: 1, max: 255, minMessage: 'Design name must be at least 1 character', maxMessage: 'Design name cannot exceed 255 characters')]
        public readonly ?string $name = null,

        #[Assert\Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')]
        public readonly ?string $description = null,

        #[Assert\Type('array', message: 'Design data must be an array')]
        public readonly ?array $data = null,

        #[Assert\Type('integer', message: 'Project ID must be an integer')]
        #[Assert\Positive(message: 'Project ID must be positive')]
        public readonly ?int $projectId = null,

        #[Assert\Type('integer', message: 'Width must be an integer')]
        #[Assert\Positive(message: 'Width must be positive')]
        #[Assert\Range(min: 1, max: 10000, notInRangeMessage: 'Width must be between 1 and 10000 pixels')]
        public readonly ?int $width = null,

        #[Assert\Type('integer', message: 'Height must be an integer')]
        #[Assert\Positive(message: 'Height must be positive')]
        #[Assert\Range(min: 1, max: 10000, notInRangeMessage: 'Height must be between 1 and 10000 pixels')]
        public readonly ?int $height = null,

        #[Assert\Type('bool', message: 'Is public must be a boolean')]
        public readonly ?bool $isPublic = null,
    ) {}

    public function hasAnyData(): bool
    {
        return $this->name !== null
            || $this->description !== null
            || $this->data !== null
            || $this->projectId !== null
            || $this->width !== null
            || $this->height !== null
            || $this->isPublic !== null;
    }
}
