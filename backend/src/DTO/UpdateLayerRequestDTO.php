<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateLayerRequestDTO
{
    public function __construct(
        #[Assert\Length(max: 255, maxMessage: 'Layer name cannot be longer than 255 characters')]
        public ?string $name = null,

        #[Assert\Type(type: 'array', message: 'Properties must be an array')]
        public ?array $properties = null,

        #[Assert\Type(type: 'array', message: 'Transform must be an array')]
        public ?array $transform = null,

        #[Assert\Type(type: 'integer', message: 'Z-index must be an integer')]
        #[Assert\PositiveOrZero(message: 'Z-index must be positive or zero')]
        public ?int $zIndex = null,

        #[Assert\Type(type: 'boolean', message: 'Visible must be a boolean')]
        public ?bool $visible = null,

        #[Assert\Type(type: 'boolean', message: 'Locked must be a boolean')]
        public ?bool $locked = null,

        #[Assert\Type(type: 'string', message: 'Parent layer ID must be a string')]
        public ?string $parentLayerId = null,
    ) {
    }

    public function hasAnyData(): bool
    {
        return $this->name !== null ||
               $this->properties !== null ||
               $this->transform !== null ||
               $this->zIndex !== null ||
               $this->visible !== null ||
               $this->locked !== null ||
               $this->parentLayerId !== null;
    }
}
