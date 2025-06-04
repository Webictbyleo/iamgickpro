<?php

declare(strict_types=1);

namespace App\Service\Svg;

use DOMDocument;
use DOMElement;

/**
 * Core SVG document builder for creating valid SVG XML structure
 */
class SvgDocumentBuilder
{
    private array $namespaces = [
        'svg' => 'http://www.w3.org/2000/svg',
        'xlink' => 'http://www.w3.org/1999/xlink',
    ];

    public function __construct(private readonly bool $validateSvg = true)
    {
    }

    /**
     * Create a new SVG document with specified dimensions
     */
    public function createDocument(int $width, int $height, ?string $backgroundColor = null): DOMElement
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        
        $svgElement = $document->createElementNS($this->namespaces['svg'], 'svg');
        $svgElement->setAttribute('width', (string)$width);
        $svgElement->setAttribute('height', (string)$height);
        $svgElement->setAttribute('viewBox', "0 0 {$width} {$height}");
        $svgElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xlink', $this->namespaces['xlink']);
        
        $document->appendChild($svgElement);
        
        // Add background if specified
        if ($backgroundColor) {
            $rect = $document->createElement('rect');
            $rect->setAttribute('width', '100%');
            $rect->setAttribute('height', '100%');
            $rect->setAttribute('fill', $backgroundColor);
            $svgElement->appendChild($rect);
        }
        
        return $svgElement;
    }

    /**
     * Save document to string
     */
    public function saveDocument(DOMElement $svgElement): string
    {
        return $svgElement->ownerDocument->saveXML($svgElement) ?: '';
    }

    public function createGroup(?string $id = null): DOMElement
    {
        $document = new DOMDocument();
        $group = $document->createElement('g');
        if ($id) {
            $group->setAttribute('id', $id);
        }
        return $group;
    }

    public function createElement(string $tagName, ?DOMDocument $context = null): DOMElement
    {
        $document = $context ?: new DOMDocument();
        return $document->createElement($tagName);
    }

    public function createText(string $content, ?DOMDocument $context = null): \DOMText
    {
        $document = $context ?: new DOMDocument();
        return $document->createTextNode($content);
    }

    public function addDefinitions(DOMElement $svgElement): DOMElement
    {
        $defs = $svgElement->ownerDocument->createElement('defs');
        $svgElement->appendChild($defs);
        return $defs;
    }

    public function createClipPath(string $id, ?DOMDocument $targetDocument = null): DOMElement
    {
        $document = $targetDocument ?? new DOMDocument();
        $clipPath = $document->createElement('clipPath');
        $clipPath->setAttribute('id', $id);
        return $clipPath;
    }

    public function createLinearGradient(string $id, array $stops, array $attributes = [], ?DOMDocument $targetDocument = null): DOMElement
    {
        $document = $targetDocument ?? new DOMDocument();
        $gradient = $document->createElement('linearGradient');
        $gradient->setAttribute('id', $id);
        
        foreach ($attributes as $attr => $value) {
            $gradient->setAttribute($attr, $value);
        }
        
        foreach ($stops as $stop) {
            $stopElement = $document->createElement('stop');
            $stopElement->setAttribute('offset', $stop['offset'] ?? '0%');
            $stopElement->setAttribute('stop-color', $stop['color'] ?? '#000000');
            if (isset($stop['opacity'])) {
                $stopElement->setAttribute('stop-opacity', (string)$stop['opacity']);
            }
            $gradient->appendChild($stopElement);
        }
        
        return $gradient;
    }

    public function createRadialGradient(string $id, array $stops, array $attributes = [], ?DOMDocument $targetDocument = null): DOMElement
    {
        $document = $targetDocument ?? new DOMDocument();
        $gradient = $document->createElement('radialGradient');
        $gradient->setAttribute('id', $id);
        
        foreach ($attributes as $attr => $value) {
            $gradient->setAttribute($attr, $value);
        }
        
        foreach ($stops as $stop) {
            $stopElement = $document->createElement('stop');
            $stopElement->setAttribute('offset', $stop['offset'] ?? '0%');
            $stopElement->setAttribute('stop-color', $stop['color'] ?? '#000000');
            if (isset($stop['opacity'])) {
                $stopElement->setAttribute('stop-opacity', (string)$stop['opacity']);
            }
            $gradient->appendChild($stopElement);
        }
        
        return $gradient;
    }

    public function createFilter(string $id, ?DOMDocument $targetDocument = null): DOMElement
    {
        $document = $targetDocument ?? new DOMDocument();
        $filter = $document->createElement('filter');
        $filter->setAttribute('id', $id);
        return $filter;
    }

    public function createPattern(string $id, int $width, int $height, array $attributes = [], ?DOMDocument $targetDocument = null): DOMElement
    {
        $document = $targetDocument ?? new DOMDocument();
        $pattern = $document->createElement('pattern');
        $pattern->setAttribute('id', $id);
        $pattern->setAttribute('width', (string)$width);
        $pattern->setAttribute('height', (string)$height);
        $pattern->setAttribute('patternUnits', 'userSpaceOnUse');
        
        foreach ($attributes as $attr => $value) {
            $pattern->setAttribute($attr, $value);
        }
        
        return $pattern;
    }

    public function addStylesheet(DOMElement $svgElement, string $css): void
    {
        $style = $svgElement->ownerDocument->createElement('style');
        $style->setAttribute('type', 'text/css');
        $style->appendChild($svgElement->ownerDocument->createCDATASection($css));
        $svgElement->appendChild($style);
    }

    public function validateAndSanitize(DOMElement $svgElement): bool
    {
        // Basic validation - check if document is well-formed
        try {
            $svgElement->ownerDocument->saveXML($svgElement);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
