<?php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\ValueObject\LayerUpdate;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for performing bulk updates on multiple layers.
 * 
 * This DTO handles batch operations for updating layer properties, allowing
 * efficient modification of multiple layers in a single API call. Each layer
 * update includes the layer ID and the specific changes to apply.
 */
final readonly class BulkUpdateLayersRequestDTO
{
    public function __construct(
        /**
         * Array of layer updates to perform in batch.
         * 
         * Each LayerUpdate contains:
         * - id: The unique identifier of the layer to update
         * - updates: Object containing the properties to change
         * 
         * This allows for efficient bulk operations while maintaining
         * type safety and validation for each individual update.
         * 
         * @var LayerUpdate[] $layers Array of typed layer update objects
         */
        #[Assert\Type(type: 'array', message: 'Layers must be an array')]
        #[Assert\Valid]
        public array $layers,
    ) {
    }
}
