<?php

declare(strict_types=1);

namespace App\Service\Svg\LayerRenderer;

use App\Entity\Layer;
use App\Service\Svg\SvgDocumentBuilder;
use DOMElement;

/**
 * Renderer for text layers
 */
class TextLayerRenderer extends AbstractLayerRenderer
{
    public function canRender(Layer $layer): bool
    {
        return $layer->getType() === 'text';
    }

    public function getSupportedTypes(): array
    {
        return ['text'];
    }

    public function getPriority(): int
    {
        return 10;
    }

    protected function renderLayerContent(Layer $layer, SvgDocumentBuilder $builder): DOMElement
    {
        $properties = $layer->getProperties() ?? [];
        
        // Extract text properties with defaults
        $text = $this->sanitizeText($properties['text'] ?? 'Text');
        $fontSize = $this->validateNumber($properties['fontSize'] ?? 16, 12, 1, 500);
        $fontFamily = $this->sanitizeFontFamily($properties['fontFamily'] ?? 'Arial, sans-serif');
        $fontWeight = $this->sanitizeFontWeight($properties['fontWeight'] ?? 'normal');
        $fontStyle = $this->sanitizeFontStyle($properties['fontStyle'] ?? 'normal');
        $textAlign = $this->sanitizeTextAlign($properties['textAlign'] ?? 'left');
        $color = $this->validateColor($properties['color'] ?? '#000000');
        $lineHeight = $this->validateNumber($properties['lineHeight'] ?? 1.2, 0.5, 0.1, 5);
        $letterSpacing = $this->validateNumber($properties['letterSpacing'] ?? 0, 0, -10, 50);
        $textDecoration = $this->sanitizeTextDecoration($properties['textDecoration'] ?? 'none');
        
        // Handle multi-line text
        $lines = explode("\n", $text);
        $width = $layer->getWidth() ?? 200;
        $height = $layer->getHeight() ?? 50;
        
        if (count($lines) === 1 && !($properties['wordWrap'] ?? false)) {
            // Single line text
            return $this->createSingleLineText($builder, $text, $fontSize, $fontFamily, $fontWeight, $fontStyle, $textAlign, $color, $letterSpacing, $textDecoration, $width, $height);
        } else {
            // Multi-line text
            return $this->createMultiLineText($builder, $lines, $fontSize, $fontFamily, $fontWeight, $fontStyle, $textAlign, $color, $lineHeight, $letterSpacing, $textDecoration, $width, $height, $properties['wordWrap'] ?? false);
        }
    }

    private function createSingleLineText(
        SvgDocumentBuilder $builder,
        string $text,
        float $fontSize,
        string $fontFamily,
        string $fontWeight,
        string $fontStyle,
        string $textAlign,
        string $color,
        float $letterSpacing,
        string $textDecoration,
        float $width,
        float $height
    ): DOMElement {
        // Use the builder's document context instead of creating a new one
        $textElement = $builder->createElement('text');
        
        // Position based on alignment
        $x = $this->calculateTextX($textAlign, $width);
        $y = $height / 2; // Center vertically
        
        $textElement->setAttribute('x', (string)$x);
        $textElement->setAttribute('y', (string)$y);
        $textElement->setAttribute('font-family', $fontFamily);
        $textElement->setAttribute('font-size', (string)$fontSize);
        $textElement->setAttribute('font-weight', $fontWeight);
        $textElement->setAttribute('font-style', $fontStyle);
        $textElement->setAttribute('text-anchor', $this->convertTextAlign($textAlign));
        $textElement->setAttribute('dominant-baseline', 'middle');
        $textElement->setAttribute('fill', $color);
        
        if ($letterSpacing !== 0) {
            $textElement->setAttribute('letter-spacing', (string)$letterSpacing);
        }
        
        if ($textDecoration !== 'none') {
            $textElement->setAttribute('text-decoration', $textDecoration);
        }
        
        $textElement->appendChild($builder->createText($text, $textElement->ownerDocument));
        
        return $textElement;
    }

    private function createMultiLineText(
        SvgDocumentBuilder $builder,
        array $lines,
        float $fontSize,
        string $fontFamily,
        string $fontWeight,
        string $fontStyle,
        string $textAlign,
        string $color,
        float $lineHeight,
        float $letterSpacing,
        string $textDecoration,
        float $width,
        float $height,
        bool $wordWrap
    ): DOMElement {
        // Use the builder's document context instead of creating a new one
        $textElement = $builder->createElement('text');
        
        // Calculate position
        $x = $this->calculateTextX($textAlign, $width);
        $lineHeightPx = $fontSize * $lineHeight;
        $totalHeight = count($lines) * $lineHeightPx;
        $startY = ($height - $totalHeight) / 2 + $fontSize; // Center vertically
        
        $textElement->setAttribute('x', (string)$x);
        $textElement->setAttribute('y', (string)$startY);
        $textElement->setAttribute('font-family', $fontFamily);
        $textElement->setAttribute('font-size', (string)$fontSize);
        $textElement->setAttribute('font-weight', $fontWeight);
        $textElement->setAttribute('font-style', $fontStyle);
        $textElement->setAttribute('text-anchor', $this->convertTextAlign($textAlign));
        $textElement->setAttribute('fill', $color);
        
        if ($letterSpacing !== 0) {
            $textElement->setAttribute('letter-spacing', (string)$letterSpacing);
        }
        
        if ($textDecoration !== 'none') {
            $textElement->setAttribute('text-decoration', $textDecoration);
        }
        
        // Handle word wrapping if needed
        if ($wordWrap) {
            $lines = $this->wrapText($lines, $width, $fontSize, $fontFamily);
        }
        
        // Create tspan elements for each line
        foreach ($lines as $index => $line) {
            if (trim($line) === '') {
                continue; // Skip empty lines
            }
            
            $tspan = $builder->createElement('tspan');
            $tspan->setAttribute('x', (string)$x);
            $tspan->setAttribute('dy', $index === 0 ? '0' : (string)$lineHeightPx);
            $tspan->appendChild($builder->createText(trim($line), $textElement->ownerDocument));
            $textElement->appendChild($tspan);
        }
        
        return $textElement;
    }

    private function calculateTextX(string $textAlign, float $width): float
    {
        return match ($textAlign) {
            'center' => $width / 2,
            'right' => $width,
            default => 0, // left
        };
    }

    private function convertTextAlign(string $textAlign): string
    {
        return match ($textAlign) {
            'center' => 'middle',
            'right' => 'end',
            default => 'start', // left
        };
    }

    private function wrapText(array $lines, float $width, float $fontSize, string $fontFamily): array
    {
        // Simple word wrapping - estimate character width
        $avgCharWidth = $fontSize * 0.6; // Rough estimation
        $maxCharsPerLine = (int)($width / $avgCharWidth);
        
        if ($maxCharsPerLine <= 0) {
            return $lines;
        }
        
        $wrappedLines = [];
        foreach ($lines as $line) {
            if (strlen($line) <= $maxCharsPerLine) {
                $wrappedLines[] = $line;
            } else {
                // Simple word breaking
                $words = explode(' ', $line);
                $currentLine = '';
                
                foreach ($words as $word) {
                    if (strlen($currentLine . ' ' . $word) <= $maxCharsPerLine) {
                        $currentLine .= ($currentLine ? ' ' : '') . $word;
                    } else {
                        if ($currentLine) {
                            $wrappedLines[] = $currentLine;
                        }
                        $currentLine = $word;
                    }
                }
                
                if ($currentLine) {
                    $wrappedLines[] = $currentLine;
                }
            }
        }
        
        return $wrappedLines;
    }

    private function sanitizeFontFamily(string $fontFamily): string
    {
        // Remove dangerous content and ensure valid CSS
        $fontFamily = preg_replace('/[<>"\']/', '', $fontFamily);
        return trim($fontFamily) ?: 'Arial, sans-serif';
    }

    private function sanitizeFontWeight(string $fontWeight): string
    {
        $validWeights = ['normal', 'bold', 'bolder', 'lighter', '100', '200', '300', '400', '500', '600', '700', '800', '900'];
        return in_array($fontWeight, $validWeights, true) ? $fontWeight : 'normal';
    }

    private function sanitizeFontStyle(string $fontStyle): string
    {
        $validStyles = ['normal', 'italic', 'oblique'];
        return in_array($fontStyle, $validStyles, true) ? $fontStyle : 'normal';
    }

    private function sanitizeTextAlign(string $textAlign): string
    {
        $validAligns = ['left', 'center', 'right', 'justify'];
        return in_array($textAlign, $validAligns, true) ? $textAlign : 'left';
    }

    private function sanitizeTextDecoration(string $textDecoration): string
    {
        $validDecorations = ['none', 'underline', 'overline', 'line-through'];
        return in_array($textDecoration, $validDecorations, true) ? $textDecoration : 'none';
    }
}
