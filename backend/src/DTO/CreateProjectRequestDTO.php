<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateProjectRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Project name is required')]
        #[Assert\Length(min: 1, max: 255, minMessage: 'Project name must be at least 1 character', maxMessage: 'Project name cannot exceed 255 characters')]
        public readonly string $name,

        #[Assert\Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')]
        public readonly ?string $description = null,

        #[Assert\Type('bool', message: 'Is public must be a boolean')]
        public readonly bool $isPublic = false,

        #[Assert\Type('array', message: 'Settings must be an array')]
        public readonly array $settings = [],

        #[Assert\Type('array', message: 'Tags must be an array')]
        public readonly array $tags = [],

        #[Assert\Url(message: 'Thumbnail must be a valid URL')]
        public readonly ?string $thumbnail = null,
    ) {}
}
