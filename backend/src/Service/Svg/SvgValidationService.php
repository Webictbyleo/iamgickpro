<?php

declare(strict_types=1);

namespace App\Service\Svg;

use DOMDocument;
use Psr\Log\LoggerInterface;

/**
 * Service for validating and sanitizing SVG content
 */
class SvgValidationService
{
    private const ALLOWED_ELEMENTS = [
        'svg', 'g', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
        'path', 'text', 'tspan', 'textPath', 'image', 'use', 'defs', 'clipPath',
        'mask', 'pattern', 'linearGradient', 'radialGradient', 'stop', 'filter',
        'feGaussianBlur', 'feOffset', 'feFlood', 'feComposite', 'feMorphology',
        'feColorMatrix', 'feConvolveMatrix', 'feTurbulence', 'feDisplacementMap',
        'style', 'title', 'desc', 'metadata'
    ];

    private const ALLOWED_ATTRIBUTES = [
        'id', 'class', 'style', 'transform', 'x', 'y', 'width', 'height', 'rx', 'ry',
        'cx', 'cy', 'r', 'x1', 'y1', 'x2', 'y2', 'points', 'd', 'fill', 'stroke',
        'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-dasharray',
        'stroke-dashoffset', 'fill-opacity', 'stroke-opacity', 'opacity',
        'font-family', 'font-size', 'font-weight', 'font-style', 'text-anchor',
        'text-decoration', 'letter-spacing', 'word-spacing', 'text-transform',
        'writing-mode', 'direction', 'unicode-bidi', 'dominant-baseline',
        'alignment-baseline', 'baseline-shift', 'clip-path', 'mask', 'filter',
        'marker-start', 'marker-mid', 'marker-end', 'color', 'visibility',
        'display', 'overflow', 'clip-rule', 'fill-rule', 'viewBox', 'preserveAspectRatio',
        'gradientUnits', 'gradientTransform', 'spreadMethod', 'patternUnits',
        'patternTransform', 'patternContentUnits', 'href', 'xlink:href',
        'offset', 'stop-color', 'stop-opacity', 'type', 'values', 'dur', 'repeatCount'
    ];

    private const DANGEROUS_PATTERNS = [
        '/javascript:/i',
        '/data:(?!image\/)/i',
        '/vbscript:/i',
        '/on\w+\s*=/i',
        '/<script/i',
        '/<iframe/i',
        '/<object/i',
        '/<embed/i',
        '/<link/i',
        '/<meta/i',
        '/expression\s*\(/i',
        '/import\s*\(/i',
        '/url\s*\(\s*["\']?(?!data:image\/|#)/i'
    ];

    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function validateSvgString(string $svgContent): array
    {
        $errors = [];
        $warnings = [];

        // Check for dangerous patterns
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $svgContent)) {
                $errors[] = "Potentially dangerous content detected: {$pattern}";
            }
        }

        // Validate XML structure
        $dom = new DOMDocument();
        $previousValue = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            if (!$dom->loadXML($svgContent)) {
                $xmlErrors = libxml_get_errors();
                foreach ($xmlErrors as $error) {
                    $errors[] = "XML Error: {$error->message}";
                }
            }
        } catch (\Exception $e) {
            $errors[] = "Failed to parse XML: " . $e->getMessage();
        } finally {
            libxml_use_internal_errors($previousValue);
        }

        if (empty($errors)) {
            // Validate SVG structure
            $structureValidation = $this->validateSvgStructure($dom);
            $errors = array_merge($errors, $structureValidation['errors']);
            $warnings = array_merge($warnings, $structureValidation['warnings']);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    public function sanitizeSvgString(string $svgContent): string
    {
        $dom = new DOMDocument();
        $previousValue = libxml_use_internal_errors(true);
        
        try {
            if (!$dom->loadXML($svgContent)) {
                throw new \InvalidArgumentException('Invalid XML content');
            }

            $this->sanitizeDomTree($dom);
            
            return $dom->saveXML() ?: '';
        } finally {
            libxml_use_internal_errors($previousValue);
        }
    }

    private function validateSvgStructure(DOMDocument $dom): array
    {
        $errors = [];
        $warnings = [];

        // Check if root element is SVG
        $rootElement = $dom->documentElement;
        if (!$rootElement || $rootElement->tagName !== 'svg') {
            $errors[] = 'Root element must be <svg>';
            return ['errors' => $errors, 'warnings' => $warnings];
        }

        // Check for required attributes
        if (!$rootElement->hasAttribute('width') || !$rootElement->hasAttribute('height')) {
            $warnings[] = 'SVG should have width and height attributes';
        }

        // Validate all elements
        $this->validateElements($rootElement, $errors, $warnings);

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function validateElements(\DOMElement $element, array &$errors, array &$warnings): void
    {
        // Check if element is allowed
        if (!in_array($element->tagName, self::ALLOWED_ELEMENTS, true)) {
            $errors[] = "Element '{$element->tagName}' is not allowed";
            return;
        }

        // Validate attributes
        if ($element->hasAttributes()) {
            foreach ($element->attributes as $attribute) {
                if (!in_array($attribute->name, self::ALLOWED_ATTRIBUTES, true)) {
                    $warnings[] = "Attribute '{$attribute->name}' on element '{$element->tagName}' may not be supported";
                }

                // Check attribute values for dangerous content
                foreach (self::DANGEROUS_PATTERNS as $pattern) {
                    if (preg_match($pattern, $attribute->value)) {
                        $errors[] = "Dangerous content in attribute '{$attribute->name}': {$attribute->value}";
                    }
                }
            }
        }

        // Recursively validate child elements
        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $this->validateElements($child, $errors, $warnings);
            }
        }
    }

    private function sanitizeDomTree(DOMDocument $dom): void
    {
        $xpath = new \DOMXPath($dom);
        
        // Remove dangerous elements
        $dangerousElements = ['script', 'iframe', 'object', 'embed', 'link', 'meta'];
        foreach ($dangerousElements as $tag) {
            $elements = $xpath->query("//{$tag}");
            foreach ($elements as $element) {
                $element->parentNode?->removeChild($element);
            }
        }

        // Sanitize all elements
        $this->sanitizeElements($dom->documentElement);
    }

    private function sanitizeElements(\DOMElement $element): void
    {
        // Remove disallowed elements
        if (!in_array($element->tagName, self::ALLOWED_ELEMENTS, true)) {
            $element->parentNode?->removeChild($element);
            return;
        }

        // Sanitize attributes
        if ($element->hasAttributes()) {
            $attributesToRemove = [];
            foreach ($element->attributes as $attribute) {
                // Remove dangerous attributes
                foreach (self::DANGEROUS_PATTERNS as $pattern) {
                    if (preg_match($pattern, $attribute->value)) {
                        $attributesToRemove[] = $attribute->name;
                        break;
                    }
                }

                // Clean up attribute values
                if (!in_array($attribute->name, $attributesToRemove, true)) {
                    $cleanValue = $this->sanitizeAttributeValue($attribute->value);
                    $element->setAttribute($attribute->name, $cleanValue);
                }
            }

            // Remove dangerous attributes
            foreach ($attributesToRemove as $attrName) {
                $element->removeAttribute($attrName);
            }
        }

        // Recursively sanitize child elements
        $childElements = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $childElements[] = $child;
            }
        }

        foreach ($childElements as $child) {
            $this->sanitizeElements($child);
        }
    }

    private function sanitizeAttributeValue(string $value): string
    {
        // Remove potentially dangerous content from attribute values
        $value = preg_replace('/javascript:/i', '', $value);
        $value = preg_replace('/vbscript:/i', '', $value);
        $value = preg_replace('/data:(?!image\/)/i', '', $value);
        $value = preg_replace('/expression\s*\(/i', '', $value);
        
        return trim($value);
    }

    public function validateDimensions(int $width, int $height): array
    {
        $errors = [];
        
        if ($width <= 0) {
            $errors[] = 'Width must be greater than 0';
        }
        
        if ($height <= 0) {
            $errors[] = 'Height must be greater than 0';
        }
        
        if ($width > 10000) {
            $errors[] = 'Width too large (max 10000px)';
        }
        
        if ($height > 10000) {
            $errors[] = 'Height too large (max 10000px)';
        }
        
        return $errors;
    }

    public function validateColorValue(string $color): bool
    {
        // Validate hex colors
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            return true;
        }
        
        // Validate rgb/rgba
        if (preg_match('/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(,\s*[0-1]?(\.\d+)?)?\s*\)$/', $color)) {
            return true;
        }
        
        // Validate named colors (basic set)
        $namedColors = [
            'black', 'white', 'red', 'green', 'blue', 'yellow', 'cyan', 'magenta',
            'gray', 'grey', 'orange', 'purple', 'brown', 'pink', 'transparent', 'none'
        ];
        
        return in_array(strtolower($color), $namedColors, true);
    }
}
