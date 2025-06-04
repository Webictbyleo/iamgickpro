<?php

declare(strict_types=1);

namespace App\Service\Svg\LayerRenderer;

use App\Entity\Layer;
use App\Service\Svg\SvgDocumentBuilder;
use App\Service\Svg\SvgTransformBuilder;
use DOMElement;

/**
 * Abstract base class for layer renderers with common functionality
 */
abstract class AbstractLayerRenderer implements LayerRendererInterface
{
    public function __construct(
        protected readonly SvgTransformBuilder $transformBuilder
    ) {}

    abstract public function canRender(Layer $layer): bool;
    abstract public function getSupportedTypes(): array;
    abstract protected function renderLayerContent(Layer $layer, SvgDocumentBuilder $builder): DOMElement;

    public function render(Layer $layer, SvgDocumentBuilder $builder): ?DOMElement
    {
        if (!$this->canRender($layer) || !$layer->isVisible()) {
            return null;
        }

        // Create group for the layer
        $group = $builder->createGroup("layer-{$layer->getId()}");
        
        // Apply transform
        $transform = $this->transformBuilder->buildTransformAttribute($layer);
        if (!empty($transform)) {
            $group->setAttribute('transform', $transform);
        }

        // Apply opacity
        $opacity = $this->transformBuilder->applyOpacity($layer);
        if ($opacity !== null) {
            $group->setAttribute('opacity', $opacity);
        }

        // Render layer-specific content
        $content = $this->renderLayerContent($layer, $builder);
        if ($content) {
            // Import content to the same document as the group
            $importedContent = $group->ownerDocument->importNode($content, true);
            $group->appendChild($importedContent);
        }

        return $group;
    }

    public function getPriority(): int
    {
        return 0; // Default priority
    }

    protected function sanitizeText(string $text): string
    {
        // Remove dangerous characters and ensure valid XML
        $text = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        return trim($text);
    }

    protected function validateColor(string $color): string
    {
        // Basic color validation and sanitization
        $color = trim($color);
        
        // Check for hex colors
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            return $color;
        }
        
        // Check for rgb/rgba
        if (preg_match('/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(,\s*[0-1]?(\.\d+)?)?\s*\)$/', $color)) {
            return $color;
        }
        
        // Named colors (basic set)
        $namedColors = [
            'black' => '#000000',
            'white' => '#ffffff',
            'red' => '#ff0000',
            'green' => '#008000',
            'blue' => '#0000ff',
            'yellow' => '#ffff00',
            'cyan' => '#00ffff',
            'magenta' => '#ff00ff',
            'gray' => '#808080',
            'grey' => '#808080',
            'orange' => '#ffa500',
            'purple' => '#800080',
            'brown' => '#a52a2a',
            'pink' => '#ffc0cb',
            'transparent' => 'transparent',
            'none' => 'none'
        ];
        
        $colorLower = strtolower($color);
        if (isset($namedColors[$colorLower])) {
            return $namedColors[$colorLower];
        }
        
        // Return black as fallback
        return '#000000';
    }

    protected function validateNumber(mixed $value, float $default = 0, ?float $min = null, ?float $max = null): float
    {
        if (!is_numeric($value)) {
            return $default;
        }
        
        $num = (float)$value;
        
        if ($min !== null && $num < $min) {
            return $min;
        }
        
        if ($max !== null && $num > $max) {
            return $max;
        }
        
        return $num;
    }

    protected function createClipPath(Layer $layer, SvgDocumentBuilder $builder, DOMElement $svgElement): ?string
    {
        $width = $layer->getWidth();
        $height = $layer->getHeight();
        
        if (!$width || !$height) {
            return null;
        }
        
        $clipId = "clip-{$layer->getId()}";
        
        // Create clip path in defs
        $defs = $builder->addDefinitions($svgElement);
        $clipPath = $builder->createClipPath($clipId);
        
        $rect = $builder->createElement('rect');
        $rect->setAttribute('x', '0');
        $rect->setAttribute('y', '0');
        $rect->setAttribute('width', (string)$width);
        $rect->setAttribute('height', (string)$height);
        
        $clipPath->appendChild($rect);
        $defs->appendChild($clipPath);
        
        return "url(#{$clipId})";
    }

    protected function applyCommonAttributes(DOMElement $element, Layer $layer): void
    {
        // Apply common attributes that all layers might have
        if ($layer->getId()) {
            $element->setAttribute('data-layer-id', (string)$layer->getId());
        }
        
        if ($layer->getType()) {
            $element->setAttribute('data-layer-type', $layer->getType());
        }
    }

    protected function createGradient(array $gradientData, SvgDocumentBuilder $builder, DOMElement $svgElement): ?string
    {
        if (empty($gradientData) || !isset($gradientData['type'])) {
            return null;
        }

        $gradientId = 'gradient-' . uniqid();
        $stops = $gradientData['stops'] ?? [];
        
        if (empty($stops)) {
            return null;
        }

        $defs = $builder->addDefinitions($svgElement);
        
        if ($gradientData['type'] === 'linear') {
            $gradient = $builder->createLinearGradient($gradientId, $stops, [
                'x1' => (string)($gradientData['x1'] ?? '0%'),
                'y1' => (string)($gradientData['y1'] ?? '0%'),
                'x2' => (string)($gradientData['x2'] ?? '100%'),
                'y2' => (string)($gradientData['y2'] ?? '0%')
            ], $svgElement->ownerDocument);
        } else {
            $gradient = $builder->createRadialGradient($gradientId, $stops, [
                'cx' => (string)($gradientData['cx'] ?? '50%'),
                'cy' => (string)($gradientData['cy'] ?? '50%'),
                'r' => (string)($gradientData['r'] ?? '50%')
            ], $svgElement->ownerDocument);
        }
        
        $defs->appendChild($gradient);
        
        return "url(#{$gradientId})";
    }
}
