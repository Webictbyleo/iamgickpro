<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class BulkDeleteMediaRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'UUIDs are required')]
        #[Assert\Type('array', message: 'UUIDs must be an array')]
        #[Assert\Count(min: 1, max: 100, minMessage: 'At least one UUID is required', maxMessage: 'Cannot delete more than 100 items at once')]
        #[Assert\All([
            new Assert\NotBlank(message: 'UUID cannot be empty'),
            new Assert\Uuid(message: 'Invalid UUID format')
        ])]
        public readonly array $uuids,
    ) {}
}
