<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class BulkUpdateLayersRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Layer updates array is required')]
        #[Assert\Type(type: 'array', message: 'Layer updates must be an array')]
        #[Assert\Count(min: 1, minMessage: 'At least one layer update is required')]
        #[Assert\All([
            new Assert\Collection([
                'id' => [
                    new Assert\NotBlank(message: 'Layer ID is required'),
                    new Assert\Type(type: 'integer', message: 'Layer ID must be an integer'),
                    new Assert\Positive(message: 'Layer ID must be positive')
                ],
                'updates' => [
                    new Assert\Type(type: 'array', message: 'Updates must be an array')
                ]
            ])
        ])]
        public array $layers,
    ) {
    }
}
