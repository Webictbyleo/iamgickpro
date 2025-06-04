<?php

declare(strict_types=1);

namespace App\Service\Svg\LayerRenderer;

use App\Entity\Layer;
use App\Service\Svg\SvgDocumentBuilder;
use DOMElement;

/**
 * Renderer for group layers that contain other layers
 */
class GroupLayerRenderer extends AbstractLayerRenderer
{
    public function canRender(Layer $layer): bool
    {
        return $layer->getType() === 'group';
    }

    public function getSupportedTypes(): array
    {
        return ['group'];
    }

    public function getPriority(): int
    {
        return 5; // Lower priority since groups are containers
    }

    protected function renderLayerContent(Layer $layer, SvgDocumentBuilder $builder): DOMElement
    {
        // Create a group element for the container
        $group = $builder->createGroup("group-content-{$layer->getId()}");
        
        $properties = $layer->getProperties() ?? [];
        
        // Apply group-specific properties
        $this->applyGroupProperties($group, $properties);
        
        // Apply clipping if specified
        $clipPath = $this->createGroupClipPath($layer, $builder, $properties, $group->ownerDocument->documentElement);
        if ($clipPath) {
            $group->setAttribute('clip-path', $clipPath);
        }
        
        // Apply masking if specified
        $mask = $this->createGroupMask($layer, $builder, $properties, $group->ownerDocument->documentElement);
        if ($mask) {
            $group->setAttribute('mask', $mask);
        }
        
        // Note: Child layers will be added by the main SVG renderer service
        // This renderer only handles the group container itself
        
        $this->applyCommonAttributes($group, $layer);
        
        return $group;
    }

    private function applyGroupProperties(DOMElement $group, array $properties): void
    {
        // Apply blend mode if specified
        if (isset($properties['blendMode'])) {
            $blendMode = $this->sanitizeBlendMode($properties['blendMode']);
            if ($blendMode !== 'normal') {
                $group->setAttribute('style', "mix-blend-mode: {$blendMode};");
            }
        }
        
        // Apply isolation if specified
        if (isset($properties['isolation']) && $properties['isolation'] === true) {
            $style = $group->getAttribute('style') ?: '';
            $style .= ' isolation: isolate;';
            $group->setAttribute('style', trim($style));
        }
    }

    private function createGroupClipPath(Layer $layer, SvgDocumentBuilder $builder, array $properties, ?DOMElement $svgElement = null): ?string
    {
        // Handle both boolean and array formats for clipPath
        if (!isset($properties['clipPath'])) {
            return null;
        }
        
        $clipPathValue = $properties['clipPath'];
        
        // If it's a simple boolean and false, return null
        if (is_bool($clipPathValue) && !$clipPathValue) {
            return null;
        }
        
        // If it's a simple boolean and true, create default clip path
        if (is_bool($clipPathValue) && $clipPathValue) {
            $clipPathValue = ['enabled' => true, 'type' => 'rectangle'];
        }
        
        // If it's an array but doesn't have enabled or enabled is false, return null
        if (is_array($clipPathValue) && (!isset($clipPathValue['enabled']) || !$clipPathValue['enabled'])) {
            return null;
        }
        $clipPathData = $clipPathValue;
        $clipId = "group-clip-{$layer->getId()}";
        
        // If no SVG element provided, we need one for proper document context
        if (!$svgElement) {
            return null; // Cannot create clip path without document context
        }
        
        $defs = $builder->addDefinitions($svgElement);
        $clipPath = $builder->createClipPath($clipId, $svgElement->ownerDocument);
        
        $clipType = $clipPathData['type'] ?? 'rectangle';
        
        switch ($clipType) {
            case 'rectangle':
                $this->createRectangleClipPath($builder, $clipPath, $layer, $clipPathData);
                break;
            case 'circle':
                $this->createCircleClipPath($builder, $clipPath, $layer, $clipPathData);
                break;
            case 'ellipse':
                $this->createEllipseClipPath($builder, $clipPath, $layer, $clipPathData);
                break;
            case 'polygon':
                $this->createPolygonClipPath($builder, $clipPath, $layer, $clipPathData);
                break;
            case 'path':
                $this->createPathClipPath($builder, $clipPath, $clipPathData);
                break;
            default:
                return null;
        }
        
        $defs->appendChild($clipPath);
        return "url(#{$clipId})";
    }

    private function createGroupMask(Layer $layer, SvgDocumentBuilder $builder, array $properties, ?DOMElement $svgElement = null): ?string
    {
        // Handle both boolean and array formats for mask
        if (!isset($properties['mask'])) {
            return null;
        }
        
        $maskValue = $properties['mask'];
        
        // If it's a simple boolean and false, return null
        if (is_bool($maskValue) && !$maskValue) {
            return null;
        }
        
        // If it's a simple boolean and true, create default mask
        if (is_bool($maskValue) && $maskValue) {
            $maskValue = ['enabled' => true, 'type' => 'gradient'];
        }
        
        // If it's an array but doesn't have enabled or enabled is false, return null
        if (is_array($maskValue) && (!isset($maskValue['enabled']) || !$maskValue['enabled'])) {
            return null;
        }
        
        $maskData = $maskValue;
        $maskId = "group-mask-{$layer->getId()}";
        
        // If no SVG element provided, we need one for proper document context
        if (!$svgElement) {
            return null; // Cannot create mask without document context
        }
        
        $defs = $builder->addDefinitions($svgElement);
        $mask = $builder->createElement('mask');
        $mask->setAttribute('id', $maskId);
        
        $maskType = $maskData['type'] ?? 'gradient';
        
        switch ($maskType) {
            case 'gradient':
                $this->createGradientMask($builder, $mask, $layer, $maskData);
                break;
            case 'image':
                $this->createImageMask($builder, $mask, $layer, $maskData);
                break;
            case 'shape':
                $this->createShapeMask($builder, $mask, $layer, $maskData);
                break;
            default:
                return null;
        }
        
        $defs->appendChild($mask);
        return "url(#{$maskId})";
    }

    private function createRectangleClipPath(SvgDocumentBuilder $builder, DOMElement $clipPath, Layer $layer, array $clipData): void
    {
        $width = $layer->getWidth() ?? 100;
        $height = $layer->getHeight() ?? 100;
        
        $x = $this->validateNumber($clipData['x'] ?? 0, 0);
        $y = $this->validateNumber($clipData['y'] ?? 0, 0);
        $clipWidth = $this->validateNumber($clipData['width'] ?? $width, $width);
        $clipHeight = $this->validateNumber($clipData['height'] ?? $height, $height);
        $cornerRadius = $this->validateNumber($clipData['cornerRadius'] ?? 0, 0, 0, min($clipWidth, $clipHeight) / 2);
        
        $rect = $builder->createElement('rect');
        $rect->setAttribute('x', (string)$x);
        $rect->setAttribute('y', (string)$y);
        $rect->setAttribute('width', (string)$clipWidth);
        $rect->setAttribute('height', (string)$clipHeight);
        
        if ($cornerRadius > 0) {
            $rect->setAttribute('rx', (string)$cornerRadius);
            $rect->setAttribute('ry', (string)$cornerRadius);
        }
        
        $clipPath->appendChild($rect);
    }

    private function createCircleClipPath(SvgDocumentBuilder $builder, DOMElement $clipPath, Layer $layer, array $clipData): void
    {
        $width = $layer->getWidth() ?? 100;
        $height = $layer->getHeight() ?? 100;
        
        $cx = $this->validateNumber($clipData['cx'] ?? $width / 2, $width / 2);
        $cy = $this->validateNumber($clipData['cy'] ?? $height / 2, $height / 2);
        $r = $this->validateNumber($clipData['r'] ?? min($width, $height) / 2, min($width, $height) / 2);
        
        $circle = $builder->createElement('circle');
        $circle->setAttribute('cx', (string)$cx);
        $circle->setAttribute('cy', (string)$cy);
        $circle->setAttribute('r', (string)$r);
        
        $clipPath->appendChild($circle);
    }

    private function createEllipseClipPath(SvgDocumentBuilder $builder, DOMElement $clipPath, Layer $layer, array $clipData): void
    {
        $width = $layer->getWidth() ?? 100;
        $height = $layer->getHeight() ?? 100;
        
        $cx = $this->validateNumber($clipData['cx'] ?? $width / 2, $width / 2);
        $cy = $this->validateNumber($clipData['cy'] ?? $height / 2, $height / 2);
        $rx = $this->validateNumber($clipData['rx'] ?? $width / 2, $width / 2);
        $ry = $this->validateNumber($clipData['ry'] ?? $height / 2, $height / 2);
        
        $ellipse = $builder->createElement('ellipse');
        $ellipse->setAttribute('cx', (string)$cx);
        $ellipse->setAttribute('cy', (string)$cy);
        $ellipse->setAttribute('rx', (string)$rx);
        $ellipse->setAttribute('ry', (string)$ry);
        
        $clipPath->appendChild($ellipse);
    }

    private function createPolygonClipPath(SvgDocumentBuilder $builder, DOMElement $clipPath, Layer $layer, array $clipData): void
    {
        $points = $clipData['points'] ?? [];
        
        if (empty($points)) {
            // Create default triangle if no points specified
            $width = $layer->getWidth() ?? 100;
            $height = $layer->getHeight() ?? 100;
            $points = [
                ['x' => $width / 2, 'y' => 0],
                ['x' => 0, 'y' => $height],
                ['x' => $width, 'y' => $height]
            ];
        }
        
        $pointsString = [];
        foreach ($points as $point) {
            $x = $this->validateNumber($point['x'] ?? 0, 0);
            $y = $this->validateNumber($point['y'] ?? 0, 0);
            $pointsString[] = "{$x},{$y}";
        }
        
        $polygon = $builder->createElement('polygon');
        $polygon->setAttribute('points', implode(' ', $pointsString));
        
        $clipPath->appendChild($polygon);
    }

    private function createPathClipPath(SvgDocumentBuilder $builder, DOMElement $clipPath, array $clipData): void
    {
        $pathData = $clipData['d'] ?? '';
        
        if (empty($pathData)) {
            return;
        }
        
        // Basic validation of path data
        if (!preg_match('/^[MmLlHhVvCcSsQqTtAaZz0-9\s,.-]+$/', $pathData)) {
            return;
        }
        
        $path = $builder->createElement('path');
        $path->setAttribute('d', $pathData);
        
        $clipPath->appendChild($path);
    }

    private function createGradientMask(SvgDocumentBuilder $builder, DOMElement $mask, Layer $layer, array $maskData): void
    {
        $width = $layer->getWidth() ?? 100;
        $height = $layer->getHeight() ?? 100;
        
        $rect = $builder->createElement('rect');
        $rect->setAttribute('x', '0');
        $rect->setAttribute('y', '0');
        $rect->setAttribute('width', (string)$width);
        $rect->setAttribute('height', (string)$height);
        
        // Create gradient for mask
        $gradientId = 'mask-gradient-' . uniqid();
        $gradientData = $maskData['gradient'] ?? [
            'type' => 'linear',
            'stops' => [
                ['offset' => '0%', 'color' => '#ffffff', 'opacity' => 1],
                ['offset' => '100%', 'color' => '#000000', 'opacity' => 0]
            ]
        ];
        
        // Get the SVG element from mask's document
        $svgElement = $mask->ownerDocument->documentElement;
        
        $gradientUrl = $this->createGradient($gradientData, $builder, $svgElement);
        if ($gradientUrl) {
            $rect->setAttribute('fill', $gradientUrl);
        } else {
            $rect->setAttribute('fill', 'white');
        }
        
        $mask->appendChild($rect);
    }

    private function createImageMask(SvgDocumentBuilder $builder, DOMElement $mask, Layer $layer, array $maskData): void
    {
        $width = $layer->getWidth() ?? 100;
        $height = $layer->getHeight() ?? 100;
        $src = $maskData['src'] ?? '';
        
        if (empty($src)) {
            return;
        }
        
        $image = $builder->createElement('image');
        $image->setAttribute('x', '0');
        $image->setAttribute('y', '0');
        $image->setAttribute('width', (string)$width);
        $image->setAttribute('height', (string)$height);
        $image->setAttributeNS('http://www.w3.org/1999/xlink', 'href', $src);
        
        $mask->appendChild($image);
    }

    private function createShapeMask(SvgDocumentBuilder $builder, DOMElement $mask, Layer $layer, array $maskData): void
    {
        $width = $layer->getWidth() ?? 100;
        $height = $layer->getHeight() ?? 100;
        $shapeType = $maskData['shapeType'] ?? 'rectangle';
        
        switch ($shapeType) {
            case 'rectangle':
                $rect = $builder->createElement('rect');
                $rect->setAttribute('x', '0');
                $rect->setAttribute('y', '0');
                $rect->setAttribute('width', (string)$width);
                $rect->setAttribute('height', (string)$height);
                $rect->setAttribute('fill', 'white');
                $mask->appendChild($rect);
                break;
                
            case 'circle':
                $circle = $builder->createElement('circle');
                $circle->setAttribute('cx', (string)($width / 2));
                $circle->setAttribute('cy', (string)($height / 2));
                $circle->setAttribute('r', (string)(min($width, $height) / 2));
                $circle->setAttribute('fill', 'white');
                $mask->appendChild($circle);
                break;
                
            case 'ellipse':
                $ellipse = $builder->createElement('ellipse');
                $ellipse->setAttribute('cx', (string)($width / 2));
                $ellipse->setAttribute('cy', (string)($height / 2));
                $ellipse->setAttribute('rx', (string)($width / 2));
                $ellipse->setAttribute('ry', (string)($height / 2));
                $ellipse->setAttribute('fill', 'white');
                $mask->appendChild($ellipse);
                break;
        }
    }

    private function sanitizeBlendMode(string $blendMode): string
    {
        $validBlendModes = [
            'normal', 'multiply', 'screen', 'overlay', 'darken', 'lighten',
            'color-dodge', 'color-burn', 'hard-light', 'soft-light',
            'difference', 'exclusion', 'hue', 'saturation', 'color', 'luminosity'
        ];
        
        return in_array($blendMode, $validBlendModes, true) ? $blendMode : 'normal';
    }
}
