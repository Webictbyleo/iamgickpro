<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateMediaRequestDTO
{
    public function __construct(
        #[Assert\Length(min: 1, max: 255, minMessage: 'File name must be at least 1 character', maxMessage: 'File name cannot exceed 255 characters')]
        public readonly ?string $name = null,

        #[Assert\Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')]
        public readonly ?string $description = null,

        #[Assert\Type('array', message: 'Tags must be an array')]
        public readonly ?array $tags = null,

        #[Assert\Type('array', message: 'Metadata must be an array')]
        public readonly ?array $metadata = null,

        #[Assert\Type('bool', message: 'Is premium must be a boolean')]
        public readonly ?bool $isPremium = null,

        #[Assert\Type('bool', message: 'Is active must be a boolean')]
        public readonly ?bool $isActive = null,

        #[Assert\Type('bool', message: 'Is public must be a boolean')]
        public readonly ?bool $isPublic = null,
    ) {}

    public function hasAnyData(): bool
    {
        return $this->name !== null
            || $this->description !== null
            || $this->tags !== null
            || $this->metadata !== null
            || $this->isPremium !== null
            || $this->isActive !== null
            || $this->isPublic !== null;
    }
}
