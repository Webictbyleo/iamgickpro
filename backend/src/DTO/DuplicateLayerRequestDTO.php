<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for layer duplication requests.
 * 
 * Handles the duplication of design layers with optional customization
 * of the duplicate layer name and target design. Used by the layer
 * management system to create copies of existing layers within the
 * same design or across different designs.
 */
final readonly class DuplicateLayerRequestDTO
{
    public function __construct(
        /**
         * Custom name for the duplicated layer.
         * 
         * If provided, this will be used as the name for the new layer.
         * If null, the system will automatically generate a name based
         * on the original layer name (e.g., "Original Name Copy").
         * Must not exceed 255 characters.
         */
        #[Assert\Length(max: 255, maxMessage: 'New layer name cannot be longer than 255 characters')]
        public ?string $name = null,

        /**
         * Target design ID for cross-design duplication.
         * 
         * If provided, the layer will be duplicated into the specified
         * design instead of the current design. The user must have
         * write access to the target design. If null, the layer is
         * duplicated within the same design.
         */
        #[Assert\Type(type: 'string', message: 'Target design ID must be a string')]
        public ?string $targetDesignId = null,
    ) {
    }
}
