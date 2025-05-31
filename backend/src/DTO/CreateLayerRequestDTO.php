<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateLayerRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Design ID is required')]
        #[Assert\Type(type: 'string', message: 'Design ID must be a string')]
        public string $designId,

        #[Assert\NotBlank(message: 'Layer type is required')]
        #[Assert\Choice(
            choices: ['text', 'image', 'shape', 'group', 'video', 'audio'],
            message: 'Invalid layer type'
        )]
        public string $type,

        #[Assert\NotBlank(message: 'Layer name is required')]
        #[Assert\Length(max: 255, maxMessage: 'Layer name cannot be longer than 255 characters')]
        public string $name,

        #[Assert\Type(type: 'array', message: 'Properties must be an array')]
        public array $properties = [],

        #[Assert\Type(type: 'array', message: 'Transform must be an array')]
        public array $transform = [],

        #[Assert\Type(type: 'integer', message: 'Z-index must be an integer')]
        #[Assert\PositiveOrZero(message: 'Z-index must be positive or zero')]
        public ?int $zIndex = null,

        #[Assert\Type(type: 'boolean', message: 'Visible must be a boolean')]
        public ?bool $visible = true,

        #[Assert\Type(type: 'boolean', message: 'Locked must be a boolean')]
        public ?bool $locked = false,

        #[Assert\Type(type: 'string', message: 'Parent layer ID must be a string')]
        public ?string $parentLayerId = null,
    ) {
    }
}
