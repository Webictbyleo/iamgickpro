<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateExportJobRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Design ID is required')]
        #[Assert\Type(type: 'integer', message: 'Design ID must be an integer')]
        #[Assert\Positive(message: 'Design ID must be positive')]
        public int $designId,

        #[Assert\NotBlank(message: 'Format is required')]
        #[Assert\Choice(choices: ['png', 'jpeg', 'svg', 'pdf', 'mp4', 'gif'], message: 'Invalid format')]
        public string $format = 'png',

        #[Assert\Choice(choices: ['low', 'medium', 'high', 'ultra'], message: 'Invalid quality')]
        public string $quality = 'medium',

        #[Assert\Type(type: 'integer', message: 'Width must be an integer')]
        #[Assert\Positive(message: 'Width must be positive')]
        public ?int $width = null,

        #[Assert\Type(type: 'integer', message: 'Height must be an integer')]
        #[Assert\Positive(message: 'Height must be positive')]
        public ?int $height = null,

        #[Assert\Type(type: 'float', message: 'Scale must be a number')]
        #[Assert\PositiveOrZero(message: 'Scale must be positive or zero')]
        public ?float $scale = null,

        #[Assert\Type(type: 'bool', message: 'Transparent must be a boolean')]
        public bool $transparent = false,

        #[Assert\Type(type: 'string', message: 'Background color must be a string')]
        public ?string $backgroundColor = null,

        #[Assert\Type(type: 'array', message: 'Animation settings must be an array')]
        public ?array $animationSettings = null,
    ) {
    }
}
