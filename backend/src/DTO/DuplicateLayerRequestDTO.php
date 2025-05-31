<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DuplicateLayerRequestDTO
{
    public function __construct(
        #[Assert\Length(max: 255, maxMessage: 'New layer name cannot be longer than 255 characters')]
        public ?string $name = null,

        #[Assert\Type(type: 'string', message: 'Target design ID must be a string')]
        public ?string $targetDesignId = null,
    ) {
    }
}
