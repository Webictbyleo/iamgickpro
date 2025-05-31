<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class MoveLayerRequestDTO
{
    public function __construct(
        #[Assert\Choice(choices: ['up', 'down', 'top', 'bottom'], message: 'Direction must be one of: up, down, top, bottom')]
        public ?string $direction = null,

        #[Assert\Type(type: 'integer', message: 'Target Z-index must be an integer')]
        #[Assert\PositiveOrZero(message: 'Target Z-index must be positive or zero')]
        public ?int $targetZIndex = null,
    ) {
    }
}
