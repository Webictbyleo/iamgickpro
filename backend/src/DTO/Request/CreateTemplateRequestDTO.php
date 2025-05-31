<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateTemplateRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Template name is required')]
        #[Assert\Length(max: 255, maxMessage: 'Template name cannot be longer than 255 characters')]
        public string $name,

        #[Assert\Length(max: 1000, maxMessage: 'Description cannot be longer than 1000 characters')]
        public ?string $description = null,

        #[Assert\NotBlank(message: 'Category is required')]
        #[Assert\Choice(choices: ['social-media', 'presentation', 'print', 'marketing', 'document', 'logo', 'web-graphics', 'video', 'animation'], message: 'Invalid category')]
        public string $category = 'social-media',

        #[Assert\Type(type: 'array', message: 'Tags must be an array')]
        public array $tags = [],

        #[Assert\NotBlank(message: 'Width is required')]
        #[Assert\Type(type: 'integer', message: 'Width must be an integer')]
        #[Assert\Positive(message: 'Width must be positive')]
        public int $width,

        #[Assert\NotBlank(message: 'Height is required')]
        #[Assert\Type(type: 'integer', message: 'Height must be an integer')]
        #[Assert\Positive(message: 'Height must be positive')]
        public int $height,

        #[Assert\Type(type: 'array', message: 'Canvas settings must be an array')]
        public array $canvasSettings = [],

        #[Assert\Type(type: 'array', message: 'Layers must be an array')]
        public array $layers = [],

        #[Assert\Url(message: 'Thumbnail URL must be a valid URL')]
        public ?string $thumbnailUrl = null,

        #[Assert\Url(message: 'Preview URL must be a valid URL')]
        public ?string $previewUrl = null,

        #[Assert\Type(type: 'bool', message: 'isPremium must be a boolean')]
        public bool $isPremium = false,

        #[Assert\Type(type: 'bool', message: 'isActive must be a boolean')]
        public bool $isActive = true,
    ) {
    }
}
