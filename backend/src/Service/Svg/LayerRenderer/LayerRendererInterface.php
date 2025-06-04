<?php

declare(strict_types=1);

namespace App\Service\Svg\LayerRenderer;

use App\Entity\Layer;
use App\Service\Svg\SvgDocumentBuilder;
use DOMElement;

/**
 * Interface for layer-specific SVG renderers
 */
interface LayerRendererInterface
{
    /**
     * Check if this renderer can handle the given layer type
     */
    public function canRender(Layer $layer): bool;

    /**
     * Render the layer to SVG and return the DOM element
     */
    public function render(Layer $layer, SvgDocumentBuilder $builder): ?DOMElement;

    /**
     * Get the supported layer type(s)
     */
    public function getSupportedTypes(): array;

    /**
     * Get the priority for this renderer (higher = higher priority)
     */
    public function getPriority(): int;
}
