<?php

declare(strict_types=1);

namespace App\DTO\ValueObject;

/**
 * Base class for layer properties - different layer types extend this
 */
abstract readonly class LayerProperties
{
    /**
     * Convert properties to array for storage
     */
    abstract public function toArray(): array;

    /**
     * Create properties from array data
     */
    abstract public static function fromArray(array $data): static;
}
