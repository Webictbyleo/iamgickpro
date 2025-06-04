<?php

declare(strict_types=1);

namespace App\Service\Svg;

use App\Entity\Design;
use App\Entity\Layer;
use Psr\Log\LoggerInterface;

/**
 * Service for handling SVG error recovery and fallbacks
 */
class SvgErrorRecoveryService
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function createFallbackSvg(Design $design, ?\Exception $originalError = null): string
    {
        $this->logger->warning('Creating fallback SVG for design', [
            'design_id' => $design->getId(),
            'error' => $originalError?->getMessage()
        ]);

        $width = $design->getWidth();
        $height = $design->getHeight();
        
        // Get background color from background array
        $background = $design->getBackground();
        $backgroundColor = $background['color'] ?? '#ffffff';
        
        // Create a simple fallback SVG
        return sprintf(
            '<svg width="%d" height="%d" viewBox="0 0 %d %d" xmlns="http://www.w3.org/2000/svg">' .
            '<rect width="100%%" height="100%%" fill="%s"/>' .
            '<rect width="100%%" height="100%%" fill="#f3f4f6" fill-opacity="0.8"/>' .
            '<text x="50%%" y="50%%" text-anchor="middle" dominant-baseline="middle" ' .
            'font-family="Arial, sans-serif" font-size="16" fill="#ef4444">' .
            'Design rendering failed</text>' .
            '</svg>',
            $width, $height, $width, $height,
            htmlspecialchars($backgroundColor)
        );
    }

    public function recoverCorruptedLayer(Layer $layer): Layer
    {
        $this->logger->info('Attempting to recover corrupted layer', [
            'layer_id' => $layer->getId(),
            'layer_type' => $layer->getType()
        ]);

        // Create a new layer with safe default values
        $recoveredLayer = clone $layer;
        
        // Reset potentially corrupted transform values
        if ($this->isInvalidTransform($layer)) {
            $recoveredLayer->setX(0);
            $recoveredLayer->setY(0);
            $recoveredLayer->setWidth(100);
            $recoveredLayer->setHeight(100);
            $recoveredLayer->setRotation(0);
            $recoveredLayer->setScaleX(1);
            $recoveredLayer->setScaleY(1);
            $recoveredLayer->setOpacity(1);
        }

        // Reset invalid z-index
        if (!is_numeric($layer->getZIndex()) || $layer->getZIndex() < 0) {
            $recoveredLayer->setZIndex(0);
        }

        // Validate and fix layer properties based on type
        $this->recoverLayerProperties($recoveredLayer);

        return $recoveredLayer;
    }

    private function isInvalidTransform(Layer $layer): bool
    {
        $x = $layer->getX();
        $y = $layer->getY();
        $width = $layer->getWidth();
        $height = $layer->getHeight();
        $rotation = $layer->getRotation();
        $scaleX = $layer->getScaleX();
        $scaleY = $layer->getScaleY();
        $opacity = $layer->getOpacity();

        // Check for invalid numeric values
        return !is_numeric($x) || !is_numeric($y) || 
               !is_numeric($width) || !is_numeric($height) ||
               !is_numeric($rotation) || !is_numeric($scaleX) || 
               !is_numeric($scaleY) || !is_numeric($opacity) ||
               $width <= 0 || $height <= 0 ||
               $scaleX <= 0 || $scaleY <= 0 ||
               $opacity < 0 || $opacity > 1 ||
               abs($x) > 100000 || abs($y) > 100000 ||
               $width > 100000 || $height > 100000;
    }

    private function recoverLayerProperties(Layer $layer): void
    {
        $properties = $layer->getProperties();
        if (!$properties) {
            return;
        }

        switch ($layer->getType()) {
            case 'text':
                $this->recoverTextProperties($layer, $properties);
                break;
            case 'image':
                $this->recoverImageProperties($layer, $properties);
                break;
            case 'shape':
                $this->recoverShapeProperties($layer, $properties);
                break;
        }
    }

    private function recoverTextProperties(Layer $layer, array $properties): void
    {
        $defaultProperties = [
            'text' => 'Text',
            'fontSize' => 16,
            'fontFamily' => 'Arial, sans-serif',
            'fontWeight' => 'normal',
            'fontStyle' => 'normal',
            'textAlign' => 'left',
            'color' => '#000000',
            'lineHeight' => 1.2
        ];

        $recoveredProperties = array_merge($defaultProperties, array_filter($properties, function($value) {
            return $value !== null && $value !== '';
        }));

        // Validate specific properties
        if (!is_numeric($recoveredProperties['fontSize']) || $recoveredProperties['fontSize'] <= 0) {
            $recoveredProperties['fontSize'] = 16;
        }

        if (!is_numeric($recoveredProperties['lineHeight']) || $recoveredProperties['lineHeight'] <= 0) {
            $recoveredProperties['lineHeight'] = 1.2;
        }

        $layer->setProperties($recoveredProperties);
    }

    private function recoverImageProperties(Layer $layer, array $properties): void
    {
        $defaultProperties = [
            'src' => '',
            'fit' => 'contain',
            'preserveAspectRatio' => true
        ];

        $recoveredProperties = array_merge($defaultProperties, array_filter($properties, function($value) {
            return $value !== null && $value !== '';
        }));

        $layer->setProperties($recoveredProperties);
    }

    private function recoverShapeProperties(Layer $layer, array $properties): void
    {
        $defaultProperties = [
            'shapeType' => 'rectangle',
            'fill' => '#cccccc',
            'stroke' => 'none',
            'strokeWidth' => 0,
            'cornerRadius' => 0
        ];

        $recoveredProperties = array_merge($defaultProperties, array_filter($properties, function($value) {
            return $value !== null && $value !== '';
        }));

        // Validate numeric properties
        if (!is_numeric($recoveredProperties['strokeWidth']) || $recoveredProperties['strokeWidth'] < 0) {
            $recoveredProperties['strokeWidth'] = 0;
        }

        if (!is_numeric($recoveredProperties['cornerRadius']) || $recoveredProperties['cornerRadius'] < 0) {
            $recoveredProperties['cornerRadius'] = 0;
        }

        $layer->setProperties($recoveredProperties);
    }
}
