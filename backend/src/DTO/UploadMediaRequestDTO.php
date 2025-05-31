<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UploadMediaRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'File name is required')]
        #[Assert\Length(min: 1, max: 255, minMessage: 'File name must be at least 1 character', maxMessage: 'File name cannot exceed 255 characters')]
        public readonly string $name,

        #[Assert\NotBlank(message: 'File type is required')]
        #[Assert\Choice(choices: ['image', 'video', 'audio', 'document'], message: 'Invalid file type')]
        public readonly string $type,

        #[Assert\Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')]
        public readonly ?string $description = null,

        #[Assert\Type('array', message: 'Tags must be an array')]
        public readonly array $tags = [],

        #[Assert\Type('bool', message: 'Is public must be a boolean')]
        public readonly bool $isPublic = false,
    ) {}
}
