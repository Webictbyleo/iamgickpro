<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for layer movement requests.
 * 
 * Handles repositioning of layers within the design canvas z-order.
 * Supports both relative movements (up/down/top/bottom) and absolute
 * positioning via z-index targeting. Used in the layer management
 * system to control layer stacking order and visual hierarchy.
 */
final readonly class MoveLayerRequestDTO
{
    public function __construct(
        /**
         * Direction to move the layer relative to its current position.
         * 
         * Supported values:
         * - 'up': Move one position forward in z-order
         * - 'down': Move one position backward in z-order  
         * - 'top': Move to highest z-index (front-most layer)
         * - 'bottom': Move to lowest z-index (back-most layer)
         * 
         * Either direction or targetZIndex should be provided, not both.
         * Null indicates absolute positioning via targetZIndex should be used.
         */
        #[Assert\Choice(choices: ['up', 'down', 'top', 'bottom'], message: 'Direction must be one of: up, down, top, bottom')]
        public ?string $direction = null,

        /**
         * Absolute z-index position to move the layer to.
         * 
         * When provided, moves the layer to this exact z-index position.
         * Must be a non-negative integer. Other layers will be adjusted
         * automatically to maintain proper z-order sequence.
         * 
         * Either targetZIndex or direction should be provided, not both.
         * Null indicates relative movement via direction should be used.
         */
        #[Assert\Type(type: 'integer', message: 'Target Z-index must be an integer')]
        #[Assert\PositiveOrZero(message: 'Target Z-index must be positive or zero')]
        public ?int $targetZIndex = null,
    ) {
    }
}
