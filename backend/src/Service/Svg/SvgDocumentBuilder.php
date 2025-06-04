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

    private ?DOMDocument $currentDocument = null;
    
    /** @var DOMElement[] Collection of definitions (gradients, patterns, filters, etc.) to be added to final SVG */
    private array $definitionCollection = [];

    public function __construct(private readonly bool $validateSvg = true)
    {
    }

    public function setDocument(DOMDocument $document): void
    {
        $this->currentDocument = $document;
    }

    /**
     * Create a new SVG document with specified dimensions
     */
    public function createDocument(int $width, int $height, ?string $backgroundColor = null): DOMElement
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        
        // Set this as the current document context
        $this->currentDocument = $document;
        
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
        $document = $this->currentDocument;
        if (!$document) {
            $document = new DOMDocument('1.0', 'UTF-8');
            $this->currentDocument = $document;
        }
        
        $group = $document->createElement('g');
        if ($id) {
            $group->setAttribute('id', $id);
        }
        return $group;
    }

    public function createElement(string $tagName, ?DOMDocument $document = null): DOMElement
    {
        $doc = $document ?? $this->currentDocument;
        if (!$doc) {
            $doc = new DOMDocument('1.0', 'UTF-8');
            $this->currentDocument = $doc;
        }
        
        return $doc->createElement($tagName);
    }

    public function createText(string $content, ?DOMDocument $context = null): \DOMText
    {
        $document = $context ?? $this->currentDocument;
        if (!$document) {
            $document = new DOMDocument('1.0', 'UTF-8');
            $this->currentDocument = $document;
        }
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

    public function getCurrentDocument(): ?DOMDocument
    {
        return $this->currentDocument;
    }

    /**
     * Add a definition (gradient, pattern, filter, etc.) to the collection
     * These will be added to the final SVG root <defs> element
     */
    public function addDefinitionToCollection(DOMElement $definition): void
    {
        $this->definitionCollection[] = $definition;
    }

    /**
     * Get all collected definitions
     * @return DOMElement[]
     */
    public function getDefinitionCollection(): array
    {
        return $this->definitionCollection;
    }

    /**
     * Clear the definition collection
     */
    public function clearDefinitionCollection(): void
    {
        $this->definitionCollection = [];
    }

    /**
     * Process all collected definitions and add them to the SVG root <defs> element
     */
    public function processDefinitions(DOMElement $svgRootElement): void
    {
        if (empty($this->definitionCollection)) {
            return;
        }

        // Find or create defs element in the root SVG
        $defs = null;
        $children = $svgRootElement->childNodes;
        
        // Look for existing defs element
        for ($i = 0; $i < $children->length; $i++) {
            $child = $children->item($i);
            if ($child instanceof DOMElement && $child->nodeName === 'defs') {
                $defs = $child;
                break;
            }
        }
        
        // Create defs if it doesn't exist
        if (!$defs) {
            $defs = $svgRootElement->ownerDocument->createElement('defs');
            // Insert defs as the first child element after any style elements
            $insertPosition = null;
            for ($i = 0; $i < $children->length; $i++) {
                $child = $children->item($i);
                if ($child instanceof DOMElement && $child->nodeName !== 'style') {
                    $insertPosition = $child;
                    break;
                }
            }
            
            if ($insertPosition) {
                $svgRootElement->insertBefore($defs, $insertPosition);
            } else {
                $svgRootElement->appendChild($defs);
            }
        }

        // Add all collected definitions to the root defs
        foreach ($this->definitionCollection as $definition) {
            // Import the definition node to the target document if needed
            if ($definition->ownerDocument !== $svgRootElement->ownerDocument) {
                $importedDef = $svgRootElement->ownerDocument->importNode($definition, true);
                $defs->appendChild($importedDef);
            } else {
                $defs->appendChild($definition);
            }
        }

        // Clear the collection after processing
        $this->clearDefinitionCollection();
    }
}
