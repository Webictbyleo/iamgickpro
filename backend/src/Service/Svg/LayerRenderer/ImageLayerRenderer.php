<?php

declare(strict_types=1);

namespace App\Service\Svg\LayerRenderer;

use App\Entity\Layer;
use App\Service\Svg\SvgDocumentBuilder;
use DOMElement;

/**
 * Renderer for image layers
 */
class ImageLayerRenderer extends AbstractLayerRenderer
{
    public function canRender(Layer $layer): bool
    {
        return $layer->getType() === 'image';
    }

    public function getSupportedTypes(): array
    {
        return ['image'];
    }

    public function getPriority(): int
    {
        return 10;
    }

    protected function renderLayerContent(Layer $layer, SvgDocumentBuilder $builder): DOMElement
    {
        $properties = $layer->getProperties() ?? [];
        
        // Extract image properties
        $src = $this->sanitizeImageSrc($properties['src'] ?? '');
        $fit = $this->sanitizeFit($properties['fit'] ?? 'contain');
        $preserveAspectRatio = $properties['preserveAspectRatio'] ?? true;
        
        $width = $layer->getWidth() ?? 100;
        $height = $layer->getHeight() ?? 100;
        
        if (empty($src)) {
            // Render placeholder for missing image
            return $this->createImagePlaceholder($builder, $width, $height);
        }
        
        // Create image element using builder's document context
        $imageElement = $builder->createElement('image');
        $imageElement->setAttribute('x', '0');
        $imageElement->setAttribute('y', '0');
        $imageElement->setAttribute('width', (string)$width);
        $imageElement->setAttribute('height', (string)$height);
        $imageElement->setAttributeNS('http://www.w3.org/1999/xlink', 'href', $src);
        
        // Apply fit and aspect ratio settings
        if ($preserveAspectRatio) {
            $aspectRatioValue = $this->getPreserveAspectRatioValue($fit);
            $imageElement->setAttribute('preserveAspectRatio', $aspectRatioValue);
        } else {
            $imageElement->setAttribute('preserveAspectRatio', 'none');
        }
        
        // Apply additional image properties
        $this->applyImageFilters($imageElement, $properties, $builder);
        $this->applyCommonAttributes($imageElement, $layer);
        
        return $imageElement;
    }

    private function sanitizeImageSrc(string $src): string
    {
        $src = trim($src);
        
        if (empty($src)) {
            return '';
        }
        
        // Allow data URLs for images (base64 encoded)
        if (preg_match('/^data:image\/[^;]+;base64,/', $src)) {
            return $src;
        }
        
        // Only allow relative paths that actually exist on the filesystem
        if (preg_match('/^[a-zA-Z0-9\/\-_.]+\.(jpg|jpeg|png|gif|svg|webp)$/i', $src)) {
            // Simple check: if the file exists as a relative path, allow it
            if (file_exists($src) && is_file($src)) {
                return $src;
            }
        }
        
        return ''; // Invalid source or file doesn't exist
    }

    private function sanitizeFit(string $fit): string
    {
        $validFits = ['contain', 'cover', 'fill', 'scale-down', 'none'];
        return in_array($fit, $validFits, true) ? $fit : 'contain';
    }

    private function getPreserveAspectRatioValue(string $fit): string
    {
        return match ($fit) {
            'cover' => 'xMidYMid slice',
            'fill' => 'none',
            'scale-down' => 'xMidYMid meet',
            'none' => 'xMidYMid meet',
            default => 'xMidYMid meet', // contain
        };
    }

    private function createImagePlaceholder(SvgDocumentBuilder $builder, float $width, float $height, ?\DOMDocument $document = null): DOMElement
    {
        // Use the builder's current document context instead of creating a temporary one
        $document = $document ?? $builder->getCurrentDocument();
        if (!$document) {
            // Only create a new document if absolutely necessary
            $tempSvg = $builder->createDocument(100, 100);
            $document = $tempSvg->ownerDocument;
        }
        
        // Create a group for the placeholder
        $group = $builder->createElement('g');
        
        // Background rectangle
        $rect = $builder->createElement('rect');
        $rect->setAttribute('x', '0');
        $rect->setAttribute('y', '0');
        $rect->setAttribute('width', (string)$width);
        $rect->setAttribute('height', (string)$height);
        $rect->setAttribute('fill', '#f0f0f0');
        $rect->setAttribute('stroke', '#cccccc');
        $rect->setAttribute('stroke-width', '1');
        $group->appendChild($rect);
        
        // Create a simple image icon
        $this->createImageIcon($builder, $group, $width, $height);
        
        // Add "Image" text
        if ($width > 60 && $height > 30) {
            $text = $builder->createElement('text');
            $text->setAttribute('x', (string)($width / 2));
            $text->setAttribute('y', (string)($height / 2 + 20));
            $text->setAttribute('text-anchor', 'middle');
            $text->setAttribute('dominant-baseline', 'middle');
            $text->setAttribute('font-family', 'Arial, sans-serif');
            $text->setAttribute('font-size', '12');
            $text->setAttribute('fill', '#888888');
            $text->appendChild($builder->createText('Image', $group->ownerDocument));
            $group->appendChild($text);
        }
        
        return $group;
    }

    private function createImageIcon(SvgDocumentBuilder $builder, DOMElement $group, float $width, float $height): void
    {
        // Simple image icon (mountain with sun)
        $iconSize = min($width, $height) * 0.3;
        $centerX = $width / 2;
        $centerY = $height / 2 - 10;
        
        if ($iconSize < 20) {
            return; // Too small to render icon
        }
        
        // Sun (circle)
        $sun = $builder->createElement('circle');
        $sun->setAttribute('cx', (string)($centerX - $iconSize / 4));
        $sun->setAttribute('cy', (string)($centerY - $iconSize / 4));
        $sun->setAttribute('r', (string)($iconSize / 8));
        $sun->setAttribute('fill', '#ffdd44');
        $group->appendChild($sun);
        
        // Mountain (triangle)
        $mountain = $builder->createElement('polygon');
        $points = [
            ($centerX - $iconSize / 2) . ',' . ($centerY + $iconSize / 4),
            $centerX . ',' . ($centerY - $iconSize / 4),
            ($centerX + $iconSize / 2) . ',' . ($centerY + $iconSize / 4)
        ];
        $mountain->setAttribute('points', implode(' ', $points));
        $mountain->setAttribute('fill', '#888888');
        $group->appendChild($mountain);
    }

    private function applyImageFilters(DOMElement $imageElement, array $properties, SvgDocumentBuilder $builder): void
    {
        $filters = [];
        
        // FILTER ORDER FOLLOWS INDUSTRY STANDARDS (Photoshop/CSS Filter best practices)
        // Order matters! Each filter is applied to the result of the previous filter
        
        // 1. Blur - Apply first as it affects the foundation of the image
        if (isset($properties['blur'])) {
            $blur = $this->validateNumber($properties['blur'], 0, 0, 50);
            if ($blur > 0.0) {
                $filters[] = "blur({$blur}px)";
            }
        }
        
        // 2. Brightness - Basic exposure adjustment
        if (isset($properties['brightness'])) {
            $brightness = $this->validateNumber($properties['brightness'], 1, 0, 3);
            if ($brightness !== 1.0) {
                $filters[] = "brightness({$brightness})";
            }
        }
        
        // 3. Contrast - Enhance/reduce contrast after brightness
        if (isset($properties['contrast'])) {
            $contrast = $this->validateNumber($properties['contrast'], 1, 0, 3);
            if ($contrast !== 1.0) {
                $filters[] = "contrast({$contrast})";
            }
        }
        
        // 4. Saturation - Color intensity adjustment
        if (isset($properties['saturation'])) {
            $saturation = $this->validateNumber($properties['saturation'], 1, 0, 3);
            if ($saturation !== 1.0) {
                $filters[] = "saturate({$saturation})";
            }
        }
        
        // 5. Hue rotation - Color shift (works on existing colors)
        if (isset($properties['hue'])) {
            $hue = $this->validateNumber($properties['hue'], 0, -360, 360);
            if ($hue !== 0.0) {
                $filters[] = "hue-rotate({$hue}deg)";
            }
        }
        
        // 6. Sepia - Color tone effect (needs color data to work with)
        if (isset($properties['sepia'])) {
            $sepia = $this->validateNumber($properties['sepia'], 0, 0, 1);
            if ($sepia > 0.0) {
                $filters[] = "sepia({$sepia})";
            }
        }
        
        // 7. Grayscale - Color removal (should be near end, before invert)
        if (isset($properties['grayscale'])) {
            $grayscale = $this->validateNumber($properties['grayscale'], 0, 0, 1);
            if ($grayscale > 0.0) {
                $filters[] = "grayscale({$grayscale})";
            }
        }
        
        // 8. Invert - Complete color reversal (should be last)
        if (isset($properties['invert'])) {
            $invert = $this->validateNumber($properties['invert'], 0, 0, 1);
            if ($invert > 0.0) {
                $filters[] = "invert({$invert})";
            }
        }
        
        // Apply CSS filter only if we have non-default filters
        if (!empty($filters)) {
            $imageElement->setAttribute('style', 'filter: ' . implode(' ', $filters) . ';');
        }
        
        // Apply SVG filters for more complex effects
        $this->applySvgFilters($imageElement, $properties, $builder);
    }

    private function applySvgFilters(DOMElement $imageElement, array $properties, SvgDocumentBuilder $builder): void
    {
        $needsFilter = false;
        $filterId = 'image-filter-' . uniqid();
        
        // Check if we need SVG filters (shadow, glow, etc.)
        if (isset($properties['shadow']) && $properties['shadow']['enabled'] ?? false) {
            $needsFilter = true;
        }
        
        if (!$needsFilter) {
            return;
        }
        
        // Get the SVG document from the builder for proper filter definition placement
        $document = $imageElement->ownerDocument;
        
        // Create filter element and add to definition collection (not immediately to defs)
        $filter = $builder->createFilter($filterId, $document);
        $filter->setAttribute('x', '-50%');
        $filter->setAttribute('y', '-50%');
        $filter->setAttribute('width', '200%');
        $filter->setAttribute('height', '200%');
        
        // Add shadow effect if enabled
        if (isset($properties['shadow']) && $properties['shadow']['enabled'] ?? false) {
            $this->addShadowFilter($builder, $filter, $properties['shadow'], $document);
        }
        
        // Add filter to collection instead of immediately to defs
        $builder->addDefinitionToCollection($filter);
        $imageElement->setAttribute('filter', "url(#{$filterId})");
    }

    private function addShadowFilter(SvgDocumentBuilder $builder, DOMElement $filter, array $shadowProps, \DOMDocument $document): void
    {
        $offsetX = $this->validateNumber($shadowProps['offsetX'] ?? 5, 5, -100, 100);
        $offsetY = $this->validateNumber($shadowProps['offsetY'] ?? 5, 5, -100, 100);
        $blur = $this->validateNumber($shadowProps['blur'] ?? 5, 5, 0, 50);
        $color = $this->validateColor($shadowProps['color'] ?? '#000000');
        $opacity = $this->validateNumber($shadowProps['opacity'] ?? 0.5, 0.5, 0, 1);
        
        // Gaussian blur for shadow
        $feGaussianBlur = $builder->createElement('feGaussianBlur', $document);
        $feGaussianBlur->setAttribute('in', 'SourceAlpha');
        $feGaussianBlur->setAttribute('stdDeviation', (string)$blur);
        $feGaussianBlur->setAttribute('result', 'blur');
        $filter->appendChild($feGaussianBlur);
        
        // Offset for shadow
        $feOffset = $builder->createElement('feOffset', $document);
        $feOffset->setAttribute('in', 'blur');
        $feOffset->setAttribute('dx', (string)$offsetX);
        $feOffset->setAttribute('dy', (string)$offsetY);
        $feOffset->setAttribute('result', 'offsetBlur');
        $filter->appendChild($feOffset);
        
        // Color for shadow
        $feFlood = $builder->createElement('feFlood', $document);
        $feFlood->setAttribute('flood-color', $color);
        $feFlood->setAttribute('flood-opacity', (string)$opacity);
        $feFlood->setAttribute('result', 'shadowColor');
        $filter->appendChild($feFlood);
        
        // Apply color to shadow
        $feComposite1 = $builder->createElement('feComposite', $document);
        $feComposite1->setAttribute('in', 'shadowColor');
        $feComposite1->setAttribute('in2', 'offsetBlur');
        $feComposite1->setAttribute('operator', 'in');
        $feComposite1->setAttribute('result', 'shadow');
        $filter->appendChild($feComposite1);
        
        // Combine original image with shadow
        $feComposite2 = $builder->createElement('feComposite', $document);
        $feComposite2->setAttribute('in', 'SourceGraphic');
        $feComposite2->setAttribute('in2', 'shadow');
        $feComposite2->setAttribute('operator', 'over');
        $filter->appendChild($feComposite2);
    }
}
