<?php

declare(strict_types=1);

namespace App\Service\MediaProcessing\Config;

/**
 * Interface for all processing configuration types
 * Used to unify different processing configuration classes
 */
interface ProcessingConfigInterface
{
    /**
     * Get the configuration as an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Get the processing type (image, video, audio)
     */
    public function getType(): string;

    /**
     * Validate the configuration
     */
    public function validate(): bool;
}
