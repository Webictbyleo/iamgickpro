<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateMediaRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Media name is required')]
        #[Assert\Length(
            min: 1,
            max: 255,
            minMessage: 'Media name must be at least {{ limit }} character long',
            maxMessage: 'Media name cannot be longer than {{ limit }} characters'
        )]
        public string $name,

        #[Assert\NotBlank(message: 'Media type is required')]
        #[Assert\Choice(
            choices: ['image', 'video', 'audio'],
            message: 'Type must be one of: image, video, audio'
        )]
        public string $type = 'image',

        #[Assert\NotBlank(message: 'MIME type is required')]
        public string $mimeType,

        #[Assert\PositiveOrZero(message: 'Size must be a positive number')]
        public int $size,

        #[Assert\NotBlank(message: 'URL is required')]
        #[Assert\Url(message: 'URL must be a valid URL')]
        public string $url,

        #[Assert\Url(message: 'Thumbnail URL must be a valid URL')]
        public ?string $thumbnailUrl = null,

        #[Assert\PositiveOrZero(message: 'Width must be a positive number')]
        public ?int $width = null,

        #[Assert\PositiveOrZero(message: 'Height must be a positive number')]
        public ?int $height = null,

        #[Assert\PositiveOrZero(message: 'Duration must be a positive number')]
        public ?float $duration = null,

        #[Assert\Choice(
            choices: ['upload', 'unsplash', 'pexels', 'pixabay'],
            message: 'Source must be one of: upload, unsplash, pexels, pixabay'
        )]
        public string $source = 'upload',

        public ?string $sourceId = null,

        #[Assert\Type('array', message: 'Metadata must be an array')]
        public ?array $metadata = null,

        #[Assert\Type('array', message: 'Tags must be an array')]
        public ?array $tags = null,

        public ?string $attribution = null,

        public ?string $license = null,

        #[Assert\Type('bool', message: 'isPremium must be a boolean')]
        public bool $isPremium = false,

        #[Assert\Type('bool', message: 'isActive must be a boolean')]
        public bool $isActive = true,
    ) {
    }
}
