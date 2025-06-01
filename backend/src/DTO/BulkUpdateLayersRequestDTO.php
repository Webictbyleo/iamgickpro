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
        #[Assert\NotBlank(message: 'Layer updates array is required')]
        #[Assert\Count(min: 1, minMessage: 'At least one layer update is required')]
        #[Assert\Valid]
        public array $layers,
    ) {
    }

    /**
     * Converts the typed LayerUpdate objects to legacy array format.
     * 
     * This method provides backward compatibility with existing code
     * that expects array-based layer update data.
     * 
     * @return array<int, array{id: int, updates: array<string, mixed>}>
     */
    public function getLayersArray(): array
    {
        return array_map(
            fn(LayerUpdate $layerUpdate) => $layerUpdate->toArray(),
            $this->layers
        );
    }

    /**
     * Creates LayerUpdate objects from array data.
     * 
     * This factory method allows creating typed LayerUpdate objects
     * from legacy array-based input data.
     * 
     * @param array<int, array{id: int, updates: array<string, mixed>}> $layersData
     * @return LayerUpdate[]
     */
    public static function createLayerUpdatesFromArray(array $layersData): array
    {
        return array_map(
            fn(array $layerData) => LayerUpdate::fromArray($layerData),
            $layersData
        );
    }
}
