<?php

declare(strict_types=1);

namespace App\Service\Svg\LayerRenderer;

use App\Entity\Layer;
use App\Service\Svg\SvgDocumentBuilder;
use DOMElement;

/**
 * Renderer for shape layers (rectangle, circle, ellipse, polygon, star, etc.)
 */
class ShapeLayerRenderer extends AbstractLayerRenderer
{
    public function canRender(Layer $layer): bool
    {
        return $layer->getType() === 'shape';
    }

    public function getSupportedTypes(): array
    {
        return ['shape'];
    }

    public function getPriority(): int
    {
        return 10;
    }

    protected function renderLayerContent(Layer $layer, SvgDocumentBuilder $builder): DOMElement
    {
        $properties = $layer->getProperties() ?? [];
        
        // Extract shape properties
        $shapeType = $this->sanitizeShapeType($properties['shapeType'] ?? 'rectangle');
        $stroke = $this->validateColor($properties['stroke'] ?? 'none');
        $strokeWidth = $this->validateNumber($properties['strokeWidth'] ?? 0, 0, 0, 100);
        $strokeOpacity = $this->validateNumber($properties['strokeOpacity'] ?? 1.0, 1.0, 0.0, 1.0);
        $strokeDashArray = $this->sanitizeStrokeDashArray($properties['strokeDashArray'] ?? null);
        $strokeLineCap = $this->sanitizeStrokeLineCap($properties['strokeLineCap'] ?? 'butt');
        $strokeLineJoin = $this->sanitizeStrokeLineJoin($properties['strokeLineJoin'] ?? 'miter');
        
        $width = $layer->getWidth() ?? 100;
        $height = $layer->getHeight() ?? 100;
        
        // Create shape element based on type
        $shapeElement = $this->createShapeElement($builder, $shapeType, $width, $height, $properties);
        
        // Now get fill value with proper document context from the created element
        $fill = $this->getFillValue($properties, $builder, $shapeElement->ownerDocument->documentElement);
        
        // Apply common styling
        $shapeElement->setAttribute('fill', $fill);
        
        // Apply fill opacity if specified in the unified fill structure
        $fillConfig = $properties['fill'] ?? ['type' => 'solid', 'color' => '#cccccc', 'opacity' => 1.0];
        if (is_array($fillConfig) && isset($fillConfig['opacity']) && $fillConfig['opacity'] < 1.0) {
            $shapeElement->setAttribute('fill-opacity', (string)$fillConfig['opacity']);
        }
        
        if ($stroke !== 'none' && $strokeWidth > 0) {
            $shapeElement->setAttribute('stroke', $stroke);
            $shapeElement->setAttribute('stroke-width', (string)$strokeWidth);
            
            if ($strokeOpacity < 1.0) {
                $shapeElement->setAttribute('stroke-opacity', (string)$strokeOpacity);
            }
            
            if ($strokeDashArray) {
                $shapeElement->setAttribute('stroke-dasharray', $strokeDashArray);
            }
            
            $shapeElement->setAttribute('stroke-linecap', $strokeLineCap);
            $shapeElement->setAttribute('stroke-linejoin', $strokeLineJoin);
        }
        
        // Apply additional effects
        $this->applyShapeEffects($shapeElement, $properties, $builder);
        $this->applyCommonAttributes($shapeElement, $layer);
        
        return $shapeElement;
    }

    private function sanitizeShapeType(string $shapeType): string
    {
        $validTypes = ['rectangle', 'circle', 'ellipse', 'triangle', 'polygon', 'star', 'line', 'arrow'];
        return in_array($shapeType, $validTypes, true) ? $shapeType : 'rectangle';
    }

    private function getFillValue(array $properties, SvgDocumentBuilder $builder, ?DOMElement $svgElement = null): string
    {
        // Get the unified fill configuration
        $fill = $properties['fill'] ?? ['type' => 'solid', 'color' => '#cccccc', 'opacity' => 1.0];
        
        if (!is_array($fill) || !isset($fill['type'])) {
            return $this->validateColor('#cccccc'); // fallback to default solid color
        }
        
        return match ($fill['type']) {
            'linear', 'radial' => $this->handleGradientFill($fill, $builder, $svgElement),
            'pattern' => $this->handlePatternFill($fill, $builder, $svgElement),
            'solid' => $this->handleSolidFill($fill),
            default => $this->validateColor('#cccccc'), // fallback to default solid color
        };
    }
    
    private function handleSolidFill(array $fill): string
    {
        $color = $this->validateColor($fill['color'] ?? '#cccccc');
        $opacity = $fill['opacity'] ?? 1.0;
        
        // If opacity is not 1.0, we need to apply it to the color
        if ($opacity < 1.0) {
            // Convert hex to rgba or apply fill-opacity attribute
            return $color; // For now, return the color and handle opacity separately if needed
        }
        
        return $color;
    }
    
    private function handleGradientFill(array $fill, SvgDocumentBuilder $builder, ?DOMElement $svgElement = null): string
    {
        // If no SVG element provided, we need one for proper document context
        if (!$svgElement) {
            error_log("ShapeLayerRenderer: No SVG element provided for gradient");
            return $this->validateColor('#cccccc'); // Cannot create gradient without document context
        }
        
        // Convert unified fill structure to parent's expected gradient format
        $parentGradientData = [
            'type' => $fill['type'],
            'stops' => []
        ];
        
        // Convert colors to stops format expected by parent (offset, color, opacity)
        $colors = $fill['colors'] ?? [];
        foreach ($colors as $colorData) {
            $stop = [
                'offset' => (($colorData['stop'] ?? 0.0) * 100) . '%', // Convert 0.0-1.0 to percentage
                'color' => $colorData['color'] ?? '#000000'
            ];
            
            // Only add opacity if it's specified and not 1.0
            if (isset($colorData['opacity']) && $colorData['opacity'] < 1.0) {
                $stop['opacity'] = $colorData['opacity'];
            }
            
            $parentGradientData['stops'][] = $stop;
        }
        
        // Add type-specific parameters for parent method
        if ($fill['type'] === 'linear') {
            $angle = $fill['angle'] ?? 0;
            $angleRad = deg2rad($angle);
            
            $parentGradientData['x1'] = (0.5 - 0.5 * cos($angleRad)) * 100 . '%';
            $parentGradientData['y1'] = (0.5 - 0.5 * sin($angleRad)) * 100 . '%';
            $parentGradientData['x2'] = (0.5 + 0.5 * cos($angleRad)) * 100 . '%';
            $parentGradientData['y2'] = (0.5 + 0.5 * sin($angleRad)) * 100 . '%';
        } elseif ($fill['type'] === 'radial') {
            $parentGradientData['cx'] = ($fill['centerX'] ?? 0.5) * 100 . '%';
            $parentGradientData['cy'] = ($fill['centerY'] ?? 0.5) * 100 . '%';
            $parentGradientData['r'] = ($fill['radius'] ?? 0.5) * 100 . '%';
        }
        
        error_log("ShapeLayerRenderer: About to call parent::createGradient with data: " . json_encode($parentGradientData));
        
        // Use parent's createGradient method
        $gradientUrl = parent::createGradient($parentGradientData, $builder, $svgElement);
        
        error_log("ShapeLayerRenderer: parent::createGradient returned: " . ($gradientUrl ?: 'null'));
        
        return $gradientUrl ?: $this->validateColor('#cccccc');
    }
    
    private function handlePatternFill(array $fill, SvgDocumentBuilder $builder, ?DOMElement $svgElement = null): string
    {
        // If no SVG element provided, we need one for proper document context
        if (!$svgElement) {
            return $this->validateColor('#cccccc'); // Cannot create pattern without document context
        }
        
        // Convert unified fill structure to legacy pattern format for createPattern method
        $legacyPattern = [
            'type' => $fill['patternType'] ?? 'dots',
            'size' => $fill['size'] ?? 10,
            'color' => $fill['color'] ?? '#000000',
            'backgroundColor' => $fill['backgroundColor'] ?? '#ffffff',
            'spacing' => $fill['spacing'] ?? 20,
            'angle' => $fill['angle'] ?? 0,
        ];
        
        $patternUrl = $this->createPattern($legacyPattern, $builder, $svgElement);
        return $patternUrl ?: $this->validateColor('#cccccc');
    }

    private function createShapeElement(SvgDocumentBuilder $builder, string $shapeType, float $width, float $height, array $properties): DOMElement
    {
        return match ($shapeType) {
            'rectangle' => $this->createRectangle($builder, $width, $height, $properties),
            'circle' => $this->createCircle($builder, $width, $height),
            'ellipse' => $this->createEllipse($builder, $width, $height),
            'triangle' => $this->createTriangle($builder, $width, $height),
            'polygon' => $this->createPolygon($builder, $width, $height, $properties),
            'star' => $this->createStar($builder, $width, $height, $properties),
            'line' => $this->createLine($builder, $width, $height, $properties),
            'arrow' => $this->createArrow($builder, $width, $height, $properties),
            default => $this->createRectangle($builder, $width, $height, $properties),
        };
    }

    private function createRectangle(SvgDocumentBuilder $builder, float $width, float $height, array $properties): DOMElement
    {
        $rect = $builder->createElement('rect');
        $rect->setAttribute('x', '0');
        $rect->setAttribute('y', '0');
        $rect->setAttribute('width', (string)$width);
        $rect->setAttribute('height', (string)$height);
        
        // Apply corner radius if specified
        $cornerRadius = $this->validateNumber($properties['cornerRadius'] ?? 0, 0, 0, min($width, $height) / 2);
        if ($cornerRadius > 0) {
            $rect->setAttribute('rx', (string)$cornerRadius);
            $rect->setAttribute('ry', (string)$cornerRadius);
        }
        
        return $rect;
    }

    private function createCircle(SvgDocumentBuilder $builder, float $width, float $height): DOMElement
    {
        $circle = $builder->createElement('circle');
        $radius = min($width, $height) / 2;
        $circle->setAttribute('cx', (string)($width / 2));
        $circle->setAttribute('cy', (string)($height / 2));
        $circle->setAttribute('r', (string)$radius);
        
        return $circle;
    }

    private function createEllipse(SvgDocumentBuilder $builder, float $width, float $height): DOMElement
    {
        $ellipse = $builder->createElement('ellipse');
        $ellipse->setAttribute('cx', (string)($width / 2));
        $ellipse->setAttribute('cy', (string)($height / 2));
        $ellipse->setAttribute('rx', (string)($width / 2));
        $ellipse->setAttribute('ry', (string)($height / 2));
        
        return $ellipse;
    }

    private function createTriangle(SvgDocumentBuilder $builder, float $width, float $height): DOMElement
    {
        $polygon = $builder->createElement('polygon');
        
        // Equilateral triangle pointing up
        $points = [
            ($width / 2) . ',0',           // Top point
            '0,' . $height,                // Bottom left
            $width . ',' . $height         // Bottom right
        ];
        
        $polygon->setAttribute('points', implode(' ', $points));
        
        return $polygon;
    }

    private function createPolygon(SvgDocumentBuilder $builder, float $width, float $height, array $properties): DOMElement
    {
        $polygon = $builder->createElement('polygon');
        
        $sides = $this->validateNumber($properties['sides'] ?? 6, 6, 3, 20);
        $points = $this->calculatePolygonPoints($width, $height, (int)$sides);
        
        $polygon->setAttribute('points', implode(' ', $points));
        
        return $polygon;
    }

    private function createStar(SvgDocumentBuilder $builder, float $width, float $height, array $properties): DOMElement
    {
        $polygon = $builder->createElement('polygon');
        
        $points = $this->validateNumber($properties['points'] ?? 5, 5, 3, 20);
        $innerRadius = $this->validateNumber($properties['innerRadius'] ?? 0.4, 0.4, 0.1, 0.9);
        
        $starPoints = $this->calculateStarPoints($width, $height, (int)$points, $innerRadius);
        
        $polygon->setAttribute('points', implode(' ', $starPoints));
        
        return $polygon;
    }

    private function createLine(SvgDocumentBuilder $builder, float $width, float $height, array $properties): DOMElement
    {
        $line = $builder->createElement('line');
        
        // Default to horizontal line across the shape bounds
        $x1 = $this->validateNumber($properties['x1'] ?? 0, 0);
        $y1 = $this->validateNumber($properties['y1'] ?? $height / 2, $height / 2);
        $x2 = $this->validateNumber($properties['x2'] ?? $width, $width);
        $y2 = $this->validateNumber($properties['y2'] ?? $height / 2, $height / 2);
        
        $line->setAttribute('x1', (string)$x1);
        $line->setAttribute('y1', (string)$y1);
        $line->setAttribute('x2', (string)$x2);
        $line->setAttribute('y2', (string)$y2);
        
        return $line;
    }

    private function createArrow(SvgDocumentBuilder $builder, float $width, float $height, array $properties): DOMElement
    {
        $path = $builder->createElement('path');
        
        // Create arrow shape
        $arrowHeadSize = min($width, $height) * 0.2;
        $bodyHeight = $height * 0.4;
        $bodyWidth = $width - $arrowHeadSize;
        
        $pathData = [
            "M 0," . ($height / 2 - $bodyHeight / 2),
            "L " . $bodyWidth . "," . ($height / 2 - $bodyHeight / 2),
            "L " . $bodyWidth . ",0",
            "L " . $width . "," . ($height / 2),
            "L " . $bodyWidth . "," . $height,
            "L " . $bodyWidth . "," . ($height / 2 + $bodyHeight / 2),
            "L 0," . ($height / 2 + $bodyHeight / 2),
            "Z"
        ];
        
        $path->setAttribute('d', implode(' ', $pathData));
        
        return $path;
    }

    private function calculatePolygonPoints(float $width, float $height, int $sides): array
    {
        $points = [];
        $centerX = $width / 2;
        $centerY = $height / 2;
        $radius = min($width, $height) / 2;
        
        for ($i = 0; $i < $sides; $i++) {
            $angle = ($i * 2 * M_PI / $sides) - (M_PI / 2); // Start from top
            $x = $centerX + $radius * cos($angle);
            $y = $centerY + $radius * sin($angle);
            $points[] = $x . ',' . $y;
        }
        
        return $points;
    }

    private function calculateStarPoints(float $width, float $height, int $points, float $innerRadius): array
    {
        $starPoints = [];
        $centerX = $width / 2;
        $centerY = $height / 2;
        $outerRadius = min($width, $height) / 2;
        $innerRadiusValue = $outerRadius * $innerRadius;
        
        for ($i = 0; $i < $points * 2; $i++) {
            $angle = ($i * M_PI / $points) - (M_PI / 2);
            $radius = ($i % 2 === 0) ? $outerRadius : $innerRadiusValue;
            $x = $centerX + $radius * cos($angle);
            $y = $centerY + $radius * sin($angle);
            $starPoints[] = $x . ',' . $y;
        }
        
        return $starPoints;
    }

    private function sanitizeStrokeDashArray(?string $dashArray): ?string
    {
        if (!$dashArray) {
            return null;
        }
        
        // Validate dash array format (numbers separated by commas or spaces)
        if (preg_match('/^[\d\s,\.]+$/', $dashArray)) {
            return $dashArray;
        }
        
        return null;
    }

    private function sanitizeStrokeLineCap(string $lineCap): string
    {
        $validCaps = ['butt', 'round', 'square'];
        return in_array($lineCap, $validCaps, true) ? $lineCap : 'butt';
    }

    private function sanitizeStrokeLineJoin(string $lineJoin): string
    {
        $validJoins = ['miter', 'round', 'bevel'];
        return in_array($lineJoin, $validJoins, true) ? $lineJoin : 'miter';
    }

    private function createPattern(array $patternData, SvgDocumentBuilder $builder, ?DOMElement $svgElement = null): ?string
    {
        if (empty($patternData) || !isset($patternData['type'])) {
            return null;
        }
        
        $patternId = 'pattern-' . uniqid();
        $patternSize = $patternData['size'] ?? 20;
        
        // If no SVG element provided, we need one for proper document context
        if (!$svgElement) {
            return null; // Cannot create pattern without document context
        }
        
        $defs = $builder->addDefinitions($svgElement);
        $pattern = $builder->createPattern($patternId, $patternSize, $patternSize, [], $svgElement->ownerDocument);
        
        switch ($patternData['type']) {
            case 'dots':
                $this->createDotsPattern($builder, $pattern, $patternSize, $patternData);
                break;
            case 'stripes':
                $this->createStripesPattern($builder, $pattern, $patternSize, $patternData);
                break;
            case 'grid':
                $this->createGridPattern($builder, $pattern, $patternSize, $patternData);
                break;
            default:
                return null;
        }
        
        $defs->appendChild($pattern);
        return "url(#{$patternId})";
    }

    private function createDotsPattern(SvgDocumentBuilder $builder, DOMElement $pattern, int $size, array $data): void
    {
        $dotSize = $data['dotSize'] ?? $size * 0.3;
        $color = $this->validateColor($data['color'] ?? '#000000');
        
        $circle = $builder->createElement('circle');
        $circle->setAttribute('cx', (string)($size / 2));
        $circle->setAttribute('cy', (string)($size / 2));
        $circle->setAttribute('r', (string)($dotSize / 2));
        $circle->setAttribute('fill', $color);
        
        $pattern->appendChild($circle);
    }

    private function createStripesPattern(SvgDocumentBuilder $builder, DOMElement $pattern, int $size, array $data): void
    {
        $stripeWidth = $data['stripeWidth'] ?? $size / 2;
        $color = $this->validateColor($data['color'] ?? '#000000');
        $direction = $data['direction'] ?? 'vertical';
        
        $rect = $builder->createElement('rect');
        
        if ($direction === 'horizontal') {
            $rect->setAttribute('x', '0');
            $rect->setAttribute('y', '0');
            $rect->setAttribute('width', (string)$size);
            $rect->setAttribute('height', (string)$stripeWidth);
        } else {
            $rect->setAttribute('x', '0');
            $rect->setAttribute('y', '0');
            $rect->setAttribute('width', (string)$stripeWidth);
            $rect->setAttribute('height', (string)$size);
        }
        
        $rect->setAttribute('fill', $color);
        $pattern->appendChild($rect);
    }

    private function createGridPattern(SvgDocumentBuilder $builder, DOMElement $pattern, int $size, array $data): void
    {
        $lineWidth = $data['lineWidth'] ?? 1;
        $color = $this->validateColor($data['color'] ?? '#000000');
        
        // Vertical line
        $vLine = $builder->createElement('line');
        $vLine->setAttribute('x1', '0');
        $vLine->setAttribute('y1', '0');
        $vLine->setAttribute('x2', '0');
        $vLine->setAttribute('y2', (string)$size);
        $vLine->setAttribute('stroke', $color);
        $vLine->setAttribute('stroke-width', (string)$lineWidth);
        $pattern->appendChild($vLine);
        
        // Horizontal line
        $hLine = $builder->createElement('line');
        $hLine->setAttribute('x1', '0');
        $hLine->setAttribute('y1', '0');
        $hLine->setAttribute('x2', (string)$size);
        $hLine->setAttribute('y2', '0');
        $hLine->setAttribute('stroke', $color);
        $hLine->setAttribute('stroke-width', (string)$lineWidth);
        $pattern->appendChild($hLine);
    }

    private function applyShapeEffects(DOMElement $shapeElement, array $properties, SvgDocumentBuilder $builder): void
    {
        // Apply shadow effect
        if (isset($properties['shadow']) && $properties['shadow']['enabled'] ?? false) {
            $filterId = 'shape-shadow-' . uniqid();
            $this->createShadowFilter($builder, $filterId, $properties['shadow'], $shapeElement->ownerDocument->documentElement);
            $shapeElement->setAttribute('filter', "url(#{$filterId})");
        }
        
        // Apply glow effect
        if (isset($properties['glow']) && $properties['glow']['enabled'] ?? false) {
            $filterId = 'shape-glow-' . uniqid();
            $this->createGlowFilter($builder, $filterId, $properties['glow'], $shapeElement->ownerDocument->documentElement);
            $shapeElement->setAttribute('filter', "url(#{$filterId})");
        }
    }

    private function createShadowFilter(SvgDocumentBuilder $builder, string $filterId, array $shadowProps, ?DOMElement $svgElement = null): void
    {
        // If no SVG element provided, we need one for proper document context
        if (!$svgElement) {
            return; // Cannot create filter without document context
        }
        
        $defs = $builder->addDefinitions($svgElement);
        $filter = $builder->createFilter($filterId);
        $filter->setAttribute('x', '-50%');
        $filter->setAttribute('y', '-50%');
        $filter->setAttribute('width', '200%');
        $filter->setAttribute('height', '200%');
        
        $offsetX = $this->validateNumber($shadowProps['offsetX'] ?? 3, 3, -100, 100);
        $offsetY = $this->validateNumber($shadowProps['offsetY'] ?? 3, 3, -100, 100);
        $blur = $this->validateNumber($shadowProps['blur'] ?? 3, 3, 0, 50);
        $color = $this->validateColor($shadowProps['color'] ?? '#000000');
        $opacity = $this->validateNumber($shadowProps['opacity'] ?? 0.3, 0.3, 0, 1);
        
        // Create shadow effect
        $feGaussianBlur = $builder->createElement('feGaussianBlur');
        $feGaussianBlur->setAttribute('in', 'SourceAlpha');
        $feGaussianBlur->setAttribute('stdDeviation', (string)$blur);
        $feGaussianBlur->setAttribute('result', 'blur');
        $filter->appendChild($feGaussianBlur);
        
        $feOffset = $builder->createElement('feOffset');
        $feOffset->setAttribute('in', 'blur');
        $feOffset->setAttribute('dx', (string)$offsetX);
        $feOffset->setAttribute('dy', (string)$offsetY);
        $feOffset->setAttribute('result', 'offsetBlur');
        $filter->appendChild($feOffset);
        
        $feFlood = $builder->createElement('feFlood');
        $feFlood->setAttribute('flood-color', $color);
        $feFlood->setAttribute('flood-opacity', (string)$opacity);
        $feFlood->setAttribute('result', 'shadowColor');
        $filter->appendChild($feFlood);
        
        $feComposite1 = $builder->createElement('feComposite');
        $feComposite1->setAttribute('in', 'shadowColor');
        $feComposite1->setAttribute('in2', 'offsetBlur');
        $feComposite1->setAttribute('operator', 'in');
        $feComposite1->setAttribute('result', 'shadow');
        $filter->appendChild($feComposite1);
        
        $feComposite2 = $builder->createElement('feComposite');
        $feComposite2->setAttribute('in', 'SourceGraphic');
        $feComposite2->setAttribute('in2', 'shadow');
        $feComposite2->setAttribute('operator', 'over');
        $filter->appendChild($feComposite2);
        
        $defs->appendChild($filter);
    }

    private function createGlowFilter(SvgDocumentBuilder $builder, string $filterId, array $glowProps, ?DOMElement $svgElement = null): void
    {
        // If no SVG element provided, we need one for proper document context
        if (!$svgElement) {
            return; // Cannot create filter without document context
        }
        
        $defs = $builder->addDefinitions($svgElement);
        $filter = $builder->createFilter($filterId);
        $filter->setAttribute('x', '-50%');
        $filter->setAttribute('y', '-50%');
        $filter->setAttribute('width', '200%');
        $filter->setAttribute('height', '200%');
        
        $blur = $this->validateNumber($glowProps['blur'] ?? 5, 5, 0, 50);
        $color = $this->validateColor($glowProps['color'] ?? '#ffffff');
        $opacity = $this->validateNumber($glowProps['opacity'] ?? 0.8, 0.8, 0, 1);
        
        // Create glow effect
        $feGaussianBlur = $builder->createElement('feGaussianBlur');
        $feGaussianBlur->setAttribute('in', 'SourceGraphic');
        $feGaussianBlur->setAttribute('stdDeviation', (string)$blur);
        $feGaussianBlur->setAttribute('result', 'blur');
        $filter->appendChild($feGaussianBlur);
        
        $feFlood = $builder->createElement('feFlood');
        $feFlood->setAttribute('flood-color', $color);
        $feFlood->setAttribute('flood-opacity', (string)$opacity);
        $feFlood->setAttribute('result', 'glowColor');
        $filter->appendChild($feFlood);
        
        $feComposite1 = $builder->createElement('feComposite');
        $feComposite1->setAttribute('in', 'glowColor');
        $feComposite1->setAttribute('in2', 'blur');
        $feComposite1->setAttribute('operator', 'in');
        $feComposite1->setAttribute('result', 'glow');
        $filter->appendChild($feComposite1);
        
        $feComposite2 = $builder->createElement('feComposite');
        $feComposite2->setAttribute('in', 'SourceGraphic');
        $feComposite2->setAttribute('in2', 'glow');
        $feComposite2->setAttribute('operator', 'over');
        $filter->appendChild($feComposite2);
        
        $defs->appendChild($filter);
    }
}
