<?php

declare(strict_types=1);

namespace App\Service\Svg\LayerRenderer;

use App\Entity\Layer;
use App\Service\Svg\SvgDocumentBuilder;
use App\Service\Svg\SvgTransformBuilder;
use DOMElement;
use DOMXPath;

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

        // Create group element using the builder (ensures proper document context)
        $group = $builder->createElement('g');
        $group->setAttribute('id', "layer-{$layer->getId()}");
        
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

        // Store layer data for gradient resolution (only if layer has gradients)
        $properties = $layer->getProperties() ?? [];
        $fillConfig = $properties['fill'] ?? null;
        if ($fillConfig && is_array($fillConfig) && in_array($fillConfig['type'] ?? '', ['linear', 'radial'], true)) {
            $group->setAttribute('data-layer-id', (string)$layer->getId());
            
        }

        // Render layer-specific content
        $content = $this->renderLayerContent($layer, $builder);
        if ($content) {
            $group->appendChild($content);
        }

        return $group;
    }

    /**
     * Resolves all gradients in the SVG after all layers have been added
     * @deprecated This method is now called after all layers are rendered
     */
    public function resolveAllGradients(DOMElement $svgRoot, SvgDocumentBuilder $builder): void
    {
        return; // Deprecated, use resolveLayerGradients instead
        $xpath = new DOMXPath($svgRoot->ownerDocument);
        $layerGroups = $xpath->query('.//g[@data-layer-properties]', $svgRoot);
        
        if ($layerGroups === false) {
            return;
        }
        
        foreach ($layerGroups as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }
            
            $layerProperties = json_decode($node->getAttribute('data-layer-properties'), true);
            if (!$layerProperties || !is_array($layerProperties)) {
                continue;
            }
            
            // Resolve gradients for this layer
            $this->resolveLayerGradients($node, $layerProperties, $builder, $svgRoot);
            
            // Clean up temporary attributes
            $node->removeAttribute('data-layer-properties');
        }
    }
    
    private function resolveLayerGradients(DOMElement $layerGroup, array $layerProperties, SvgDocumentBuilder $builder, DOMElement $svgRoot): void
    {
        $fillConfig = $layerProperties['fill'] ?? null;
        if (!$fillConfig || !is_array($fillConfig)) {
            return;
        }
        
        // Create the gradient in the final SVG context
        $gradientUrl = $this->createFinalGradient($fillConfig, $builder, $svgRoot, $layerGroup->getAttribute('data-layer-id'));
        
        if ($gradientUrl) {
            // Find all shape elements in this layer group and update their fill
            $xpath = new DOMXPath($svgRoot->ownerDocument);
            $shapeElements = $xpath->query('.//rect|.//circle|.//ellipse|.//polygon|.//path|.//line', $layerGroup);
            
            if ($shapeElements !== false) {
                foreach ($shapeElements as $shapeNode) {
                    if ($shapeNode instanceof DOMElement) {
                        $currentFill = $shapeNode->getAttribute('fill');
                        // Only update if it looks like a placeholder gradient URL
                        if (str_starts_with($currentFill, 'url(#gradient-')) {
                            $shapeNode->setAttribute('fill', $gradientUrl);
                        }
                    }
                }
            }
        }
    }
    
    private function createFinalGradient(array $fillConfig, SvgDocumentBuilder $builder, DOMElement $svgRoot, string $layerId): ?string
    {
        // Generate consistent gradient ID
        $gradientId = 'gradient-' . $layerId . '-' . md5(json_encode($fillConfig));
        
        // Check if gradient already exists
        $xpath = new DOMXPath($svgRoot->ownerDocument);
        $existingGradient = $xpath->query(".//linearGradient[@id='{$gradientId}']|.//radialGradient[@id='{$gradientId}']", $svgRoot);
        
        if ($existingGradient !== false && $existingGradient->length > 0) {
            return "url(#{$gradientId})";
        }
        
        // Create gradient data in expected format
        $gradientData = [
            'type' => $fillConfig['type'],
            'stops' => [],
            'id' => $gradientId
        ];
        
        // Convert colors to stops
        $colors = $fillConfig['colors'] ?? [];
        foreach ($colors as $colorData) {
            $stop = [
                'offset' => (($colorData['stop'] ?? 0.0) * 100) . '%',
                'color' => $colorData['color'] ?? '#000000'
            ];
            
            if (isset($colorData['opacity']) && $colorData['opacity'] < 1.0) {
                $stop['opacity'] = $colorData['opacity'];
            }
            
            $gradientData['stops'][] = $stop;
        }
        
        // Add type-specific parameters
        if ($fillConfig['type'] === 'linear') {
            $angle = $fillConfig['angle'] ?? 0;
            $angleRad = deg2rad($angle);
            
            $gradientData['x1'] = (0.5 - 0.5 * cos($angleRad)) * 100 . '%';
            $gradientData['y1'] = (0.5 - 0.5 * sin($angleRad)) * 100 . '%';
            $gradientData['x2'] = (0.5 + 0.5 * cos($angleRad)) * 100 . '%';
            $gradientData['y2'] = (0.5 + 0.5 * sin($angleRad)) * 100 . '%';
        } elseif ($fillConfig['type'] === 'radial') {
            $gradientData['cx'] = ($fillConfig['centerX'] ?? 0.5) * 100 . '%';
            $gradientData['cy'] = ($fillConfig['centerY'] ?? 0.5) * 100 . '%';
            $gradientData['r'] = ($fillConfig['radius'] ?? 0.5) * 100 . '%';
        }
        
        return $this->createGradient($gradientData, $builder, $svgRoot);
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

    protected function validateColor(mixed $color): string
    {
        // Convert to string and basic validation/sanitization
        $color = trim((string)$color);
        
        // If empty or invalid, return default
        if (empty($color) || $color === '0') {
            return 'none';
        }
        
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

        // Use provided ID or generate one
        $gradientId = $gradientData['id'] ?? 'gradient-' . uniqid();
        $stops = $gradientData['stops'] ?? [];
        
        if (empty($stops)) {
            return null;
        }

        // Check if gradient already exists in the definition collection to avoid duplicates
        $existingGradients = $builder->getDefinitionCollection();
        foreach ($existingGradients as $existingGradient) {
            if ($existingGradient->getAttribute('id') === $gradientId) {
                return "url(#{$gradientId})";
            }
        }

        // Create gradient element
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
        
        // Add gradient to the definition collection instead of directly to defs
        $builder->addDefinitionToCollection($gradient);
        
        return "url(#{$gradientId})";
    }
}
