<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateProjectRequestDTO
{
    public function __construct(
        #[Assert\Length(min: 1, max: 255, minMessage: 'Project name must be at least 1 character', maxMessage: 'Project name cannot exceed 255 characters')]
        public readonly ?string $name = null,

        #[Assert\Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')]
        public readonly ?string $description = null,

        #[Assert\Type('bool', message: 'Is public must be a boolean')]
        public readonly ?bool $isPublic = null,

        #[Assert\Type('array', message: 'Settings must be an array')]
        public readonly ?array $settings = null,

        #[Assert\Type('array', message: 'Tags must be an array')]
        public readonly ?array $tags = null,

        #[Assert\Url(message: 'Thumbnail must be a valid URL')]
        public readonly ?string $thumbnail = null,
    ) {}

    public function hasAnyData(): bool
    {
        return $this->name !== null
            || $this->description !== null
            || $this->isPublic !== null
            || $this->settings !== null
            || $this->tags !== null
            || $this->thumbnail !== null;
    }
}
