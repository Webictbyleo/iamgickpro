<?php

declare(strict_types=1);

namespace App\Service\Svg\LayerRenderer;

use App\Entity\Layer;
use App\Service\Svg\SvgDocumentBuilder;
use DOMElement;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Renderer for SVG layers
 */
#[AutoconfigureTag('app.svg.layer_renderer')]
class SvgLayerRenderer extends AbstractLayerRenderer
{
    public function canRender(Layer $layer): bool
    {
        return $layer->getType() === 'svg';
    }

    public function getSupportedTypes(): array
    {
        return ['svg'];
    }

    public function getPriority(): int
    {
        return 10;
    }

    protected function renderLayerContent(Layer $layer, SvgDocumentBuilder $builder): DOMElement
    {
        $properties = $layer->getProperties() ?? [];
        
        // Extract SVG properties
        $src = $this->sanitizeSvgSrc($properties['src'] ?? '');
        $viewBox = $properties['viewBox'] ?? '0 0 100 100';
        $preserveAspectRatio = $properties['preserveAspectRatio'] ?? 'xMidYMid meet';
        
        $width = $layer->getWidth() ?? 100;
        $height = $layer->getHeight() ?? 100;
        
        if (empty($src)) {
            // Render placeholder for missing SVG
            return $this->createSvgPlaceholder($builder, $width, $height);
        }

        try {
            // Load and process the SVG content
            $svgContent = $this->loadSvgContent($src);
            if (empty($svgContent)) {
                return $this->createSvgPlaceholder($builder, $width, $height);
            }

            // Parse the SVG content
            $svgDoc = new \DOMDocument('1.0', 'UTF-8');
            $svgDoc->loadXML($svgContent);
            $sourceSvgElement = $svgDoc->documentElement;

            if (!$sourceSvgElement || $sourceSvgElement->tagName !== 'svg') {
                return $this->createSvgPlaceholder($builder, $width, $height);
            }

            // Create a new SVG element in our document
            $svgElement = $builder->createElement('svg');
            $svgElement->setAttribute('x', '0');
            $svgElement->setAttribute('y', '0');
            $svgElement->setAttribute('width', (string)$width);
            $svgElement->setAttribute('height', (string)$height);
            $svgElement->setAttribute('viewBox', $viewBox);
            $svgElement->setAttribute('preserveAspectRatio', $preserveAspectRatio);

            // Import and append all child nodes from the source SVG
            $this->importSvgContent($sourceSvgElement, $svgElement, $builder);

            // Apply fill and stroke customizations if specified
            $this->applySvgCustomizations($svgElement, $properties, $builder);

            return $svgElement;

        } catch (\Exception $e) {
            // Log error and return placeholder
            error_log("Error rendering SVG layer: " . $e->getMessage());
            return $this->createSvgPlaceholder($builder, $width, $height);
        }
    }

    private function sanitizeSvgSrc(string $src): string
    {
        $src = trim($src);
        
        if (empty($src)) {
            return '';
        }
        
        // Allow data URLs for SVGs (base64 encoded)
        if (preg_match('/^data:image\/svg\+xml;base64,/', $src)) {
            return $src;
        }
        
        // Allow relative paths and URLs
        if (preg_match('/^https?:\/\//', $src) || preg_match('/^\//', $src)) {
            return $src;
        }
        
        // Allow relative paths that exist on the filesystem
        if (preg_match('/^[a-zA-Z0-9\/\-_.]+\.svg$/i', $src)) {
            if (file_exists($src) && is_file($src)) {
                return $src;
            }
        }
        
        return ''; // Invalid source
    }

    private function loadSvgContent(string $src): string
    {
        // Handle data URLs
        if (preg_match('/^data:image\/svg\+xml;base64,(.+)$/', $src, $matches)) {
            $decoded = base64_decode($matches[1]);
            return $decoded !== false ? $decoded : '';
        }

        // Handle HTTP(S) URLs
        if (preg_match('/^https?:\/\//', $src)) {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'method' => 'GET',
                    'header' => 'User-Agent: SVGRenderer/1.0'
                ]
            ]);
            
            $content = @file_get_contents($src, false, $context);
            return $content !== false ? $content : '';
        }

        // Handle local files
        if (file_exists($src) && is_file($src)) {
            $content = @file_get_contents($src);
            return $content !== false ? $content : '';
        }

        return '';
    }

    private function importSvgContent(DOMElement $sourceSvg, DOMElement $targetSvg, SvgDocumentBuilder $builder): void
    {
        $targetDoc = $targetSvg->ownerDocument;
        
        // Import all child nodes from source SVG
        foreach ($sourceSvg->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $importedNode = $targetDoc->importNode($child, true);
                $targetSvg->appendChild($importedNode);
            }
        }

        // Copy important attributes from source SVG
        $attributesToCopy = ['xmlns', 'xmlns:xlink', 'version'];
        foreach ($attributesToCopy as $attr) {
            if ($sourceSvg->hasAttribute($attr)) {
                $targetSvg->setAttribute($attr, $sourceSvg->getAttribute($attr));
            }
        }
    }

    private function applySvgCustomizations(DOMElement $svgElement, array $properties, SvgDocumentBuilder $builder): void
    {
        $fillColors = $properties['fillColors'] ?? [];
        $strokeColors = $properties['strokeColors'] ?? [];
        $strokeWidths = $properties['strokeWidths'] ?? [];

        if (empty($fillColors) && empty($strokeColors) && empty($strokeWidths)) {
            return;
        }

        // Find all elements that can be customized
        $xpath = new \DOMXPath($svgElement->ownerDocument);
        $elements = $xpath->query('.//*[@id or @class]', $svgElement);

        if ($elements === false) {
            return;
        }

        foreach ($elements as $element) {
            if (!($element instanceof DOMElement)) {
                continue;
            }

            $id = $element->getAttribute('id');
            $class = $element->getAttribute('class');

            // Apply fill color customizations
            if (!empty($fillColors)) {
                if (!empty($id) && isset($fillColors[$id])) {
                    $element->setAttribute('fill', $fillColors[$id]);
                } elseif (!empty($class) && isset($fillColors[$class])) {
                    $element->setAttribute('fill', $fillColors[$class]);
                }
            }

            // Apply stroke color customizations
            if (!empty($strokeColors)) {
                if (!empty($id) && isset($strokeColors[$id])) {
                    $element->setAttribute('stroke', $strokeColors[$id]);
                } elseif (!empty($class) && isset($strokeColors[$class])) {
                    $element->setAttribute('stroke', $strokeColors[$class]);
                }
            }

            // Apply stroke width customizations
            if (!empty($strokeWidths)) {
                if (!empty($id) && isset($strokeWidths[$id])) {
                    $element->setAttribute('stroke-width', (string)$strokeWidths[$id]);
                } elseif (!empty($class) && isset($strokeWidths[$class])) {
                    $element->setAttribute('stroke-width', (string)$strokeWidths[$class]);
                }
            }
        }
    }

    private function createSvgPlaceholder(SvgDocumentBuilder $builder, float $width, float $height): DOMElement
    {
        // Create a placeholder rectangle for missing/invalid SVG
        $placeholder = $builder->createElement('rect');
        $placeholder->setAttribute('x', '0');
        $placeholder->setAttribute('y', '0');
        $placeholder->setAttribute('width', (string)$width);
        $placeholder->setAttribute('height', (string)$height);
        $placeholder->setAttribute('fill', '#f0f0f0');
        $placeholder->setAttribute('stroke', '#cccccc');
        $placeholder->setAttribute('stroke-width', '1');
        $placeholder->setAttribute('stroke-dasharray', '5,5');

        // Add text indicating missing SVG
        $group = $builder->createElement('g');
        $group->appendChild($placeholder);

        $text = $builder->createElement('text');
        $text->setAttribute('x', (string)($width / 2));
        $text->setAttribute('y', (string)($height / 2));
        $text->setAttribute('text-anchor', 'middle');
        $text->setAttribute('dominant-baseline', 'middle');
        $text->setAttribute('fill', '#999999');
        $text->setAttribute('font-family', 'Arial, sans-serif');
        $text->setAttribute('font-size', '12');
        $text->textContent = 'SVG';

        $group->appendChild($text);

        return $group;
    }
}
