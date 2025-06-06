<?php

declare(strict_types=1);

namespace App\Service\Svg;

use App\Entity\Design;
use App\Entity\Layer;
use App\Service\Svg\LayerRenderer\LayerRendererInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use DOMElement;

/**
 * Main SVG renderer service that coordinates all layer renderers
 */
class SvgRendererService
{
    /** @var LayerRendererInterface[] */
    private array $renderers = [];

    public function __construct(
        private readonly SvgDocumentBuilder $documentBuilder,
        private readonly SvgValidationService $validationService,
        private readonly SvgErrorRecoveryService $errorRecoveryService,
        private readonly LoggerInterface $logger,
        #[TaggedIterator('app.svg.layer_renderer')]
        iterable $renderers
    ) {
        // Sort renderers by priority (highest first)
        $renderersArray = iterator_to_array($renderers);
        usort($renderersArray, fn($a, $b) => $b->getPriority() <=> $a->getPriority());
        
        foreach ($renderersArray as $renderer) {
            $this->addRenderer($renderer);
        }
    }

    public function renderDesignToSvg(Design $design): string
    {
        try {
            return $this->doRenderDesign($design);
        } catch (\Exception $e) {
            $this->logger->error('Failed to render design to SVG', [
                'design_id' => $design->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return fallback SVG
            return $this->errorRecoveryService->createFallbackSvg($design, $e);
        }
    }

    private function doRenderDesign(Design $design): string
    {
        // Validate design dimensions
        $width = $design->getWidth() ?? 800;
        $height = $design->getHeight() ?? 600;
        
        $dimensionErrors = $this->validationService->validateDimensions($width, $height);
        if (!empty($dimensionErrors)) {
            throw new \InvalidArgumentException('Invalid design dimensions: ' . implode(', ', $dimensionErrors));
        }
        
        // Get background color from design background array
        $background = $design->getBackground();
        $backgroundColor = null;
        if (isset($background['type']) && $background['type'] === 'color') {
            $backgroundColor = $background['color'] ?? null;
        }
        
        // Create SVG document
        $svgElement = $this->documentBuilder->createDocument($width, $height, $backgroundColor);
        
        // Add canvas settings if specified
        $this->applyCanvasSettings($svgElement, $design);
        
        // Get layers sorted by z-index
        $layers = $this->getSortedLayers($design);
        
        // Render each layer
        $renderedCount = 0;
        foreach ($layers as $layer) {
            try {
                $element = $this->renderLayer($layer, $this->documentBuilder);
                if ($element) {
                    $svgElement->appendChild($element);
                    $renderedCount++;
                }
            } catch (\Exception $e) {
                $this->logger->warning('Failed to render layer', [
                    'layer_id' => $layer->getId(),
                    'layer_type' => $layer->getType(),
                    'design_id' => $design->getId(),
                    'error' => $e->getMessage()
                ]);
                
                // Try to recover the layer
                try {
                    $recoveredLayer = $this->errorRecoveryService->recoverCorruptedLayer($layer);
                    $element = $this->renderLayer($recoveredLayer, $this->documentBuilder);
                    if ($element) {
                        $svgElement->appendChild($element);
                        $renderedCount++;
                    }
                } catch (\Exception $recoveryError) {
                    $this->logger->error('Failed to recover layer', [
                        'layer_id' => $layer->getId(),
                        'recovery_error' => $recoveryError->getMessage()
                    ]);
                }
            }
        }
        
        $this->logger->info('SVG rendering completed', [
            'design_id' => $design->getId(),
            'total_layers' => count($layers),
            'rendered_layers' => $renderedCount
        ]);
        
        // Process all collected definitions (gradients, patterns, filters, etc.) and add them to the SVG root defs
        $this->documentBuilder->processDefinitions($svgElement);
        
        // Generate and validate SVG
        $svgContent = $this->documentBuilder->saveDocument($svgElement);
        $validation = $this->validationService->validateSvgString($svgContent);
        
        if (!$validation['valid']) {
            $this->logger->warning('Generated SVG has validation issues', [
                'design_id' => $design->getId(),
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings']
            ]);
            
            // Try to sanitize the SVG
            try {
                $svgContent = $this->validationService->sanitizeSvgString($svgContent);
            } catch (\Exception $e) {
                $this->logger->error('Failed to sanitize SVG', [
                    'design_id' => $design->getId(),
                    'error' => $e->getMessage()
                ]);
                
                // Return fallback SVG
                return $this->errorRecoveryService->createFallbackSvg($design, $e);
            }
        }
        
        return $svgContent;
    }

    private function renderLayer(Layer $layer, SvgDocumentBuilder $builder): ?DOMElement
    {
        if (!$layer->isVisible()) {
            return null;
        }
        
        // Find appropriate renderer
        $renderer = $this->findRenderer($layer);
        if (!$renderer) {
            $this->logger->warning('No renderer found for layer type', [
                'layer_id' => $layer->getId(),
                'layer_type' => $layer->getType()
            ]);
            return null;
        }
        
        // Render the layer
        return $renderer->render($layer, $builder);
    }

    private function findRenderer(Layer $layer): ?LayerRendererInterface
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->canRender($layer)) {
                return $renderer;
            }
        }
        
        return null;
    }

    private function getSortedLayers(Design $design): array
    {
        // Only get root-level layers (layers without parents)
        // Child layers will be rendered by their parent group renderers
        $allLayers = $design->getLayers()->toArray();
        $rootLayers = array_filter($allLayers, fn(Layer $layer) => $layer->getParent() === null);
        
        // Sort by z-index (lower values first, so they appear behind)
        usort($rootLayers, function (Layer $a, Layer $b) {
            $zIndexA = $a->getZIndex() ?? 0;
            $zIndexB = $b->getZIndex() ?? 0;
            return $zIndexA <=> $zIndexB;
        });
        
        return $rootLayers;
    }

    private function applyCanvasSettings(DOMElement $svgElement, Design $design): void
    {
        // Apply canvas-specific settings
        $canvasSettings = $design->getCanvasSettings();
        if (!$canvasSettings) {
            return;
        }
        
        // Add custom CSS styles if specified
        if (isset($canvasSettings['customCSS']) && !empty($canvasSettings['customCSS'])) {
            $css = $this->sanitizeCSS($canvasSettings['customCSS']);
            if (!empty($css)) {
                $this->documentBuilder->addStylesheet($svgElement, $css);
            }
        }
        
        // Apply grid settings for development/preview
        if (isset($canvasSettings['showGrid']) && $canvasSettings['showGrid'] === true) {
            $this->addGridOverlay($svgElement, $design);
        }
        
        // Apply safe area guidelines
        if (isset($canvasSettings['showSafeArea']) && $canvasSettings['showSafeArea'] === true) {
            $this->addSafeAreaGuides($svgElement, $design);
        }
    }

    private function sanitizeCSS(string $css): string
    {
        // Basic CSS sanitization - remove dangerous content
        $css = preg_replace('/javascript:/i', '', $css);
        $css = preg_replace('/expression\s*\(/i', '', $css);
        $css = preg_replace('/import\s*\(/i', '', $css);
        $css = preg_replace('/@import/i', '', $css);
        $css = preg_replace('/url\s*\(\s*["\']?(?!data:image\/|#)/i', '', $css);
        
        return trim($css);
    }

    private function addGridOverlay(DOMElement $svgElement, Design $design): void
    {
        $width = $design->getWidth() ?? 800;
        $height = $design->getHeight() ?? 600;
        $gridSize = 20; // 20px grid
        
        $defs = $this->documentBuilder->addDefinitions($svgElement);
        $pattern = $this->documentBuilder->createPattern('grid-pattern', $gridSize, $gridSize);
        
        // Create grid lines
        $path = $this->documentBuilder->createElement('path');
        $path->setAttribute('d', "M {$gridSize} 0 L 0 0 0 {$gridSize}");
        $path->setAttribute('fill', 'none');
        $path->setAttribute('stroke', '#e0e0e0');
        $path->setAttribute('stroke-width', '1');
        $pattern->appendChild($path);
        
        $defs->appendChild($pattern);
        
        // Apply grid to background
        $gridRect = $this->documentBuilder->createElement('rect');
        $gridRect->setAttribute('width', '100%');
        $gridRect->setAttribute('height', '100%');
        $gridRect->setAttribute('fill', 'url(#grid-pattern)');
        $gridRect->setAttribute('opacity', '0.5');
        $svgElement->appendChild($gridRect);
    }

    private function addSafeAreaGuides(DOMElement $svgElement, Design $design): void
    {
        $width = $design->getWidth() ?? 800;
        $height = $design->getHeight() ?? 600;
        $margin = min($width, $height) * 0.1; // 10% margin
        
        $guide = $this->documentBuilder->createElement('rect');
        $guide->setAttribute('x', (string)$margin);
        $guide->setAttribute('y', (string)$margin);
        $guide->setAttribute('width', (string)($width - 2 * $margin));
        $guide->setAttribute('height', (string)($height - 2 * $margin));
        $guide->setAttribute('fill', 'none');
        $guide->setAttribute('stroke', '#ff6b6b');
        $guide->setAttribute('stroke-width', '2');
        $guide->setAttribute('stroke-dasharray', '10,5');
        $guide->setAttribute('opacity', '0.7');
        
        $svgElement->appendChild($guide);
    }

    public function addRenderer(LayerRendererInterface $renderer): void
    {
        foreach ($renderer->getSupportedTypes() as $type) {
            if (!isset($this->renderers[$type]) || $renderer->getPriority() > $this->renderers[$type]->getPriority()) {
                $this->renderers[$type] = $renderer;
            }
        }
    }

    public function getSupportedLayerTypes(): array
    {
        return array_keys($this->renderers);
    }

    public function validateDesign(Design $design): array
    {
        $errors = [];
        $warnings = [];
        
        // Validate design dimensions
        $width = $design->getWidth();
        $height = $design->getHeight();
        
        if (!$width || !$height) {
            $errors[] = 'Design must have valid width and height';
        } else {
            $dimensionErrors = $this->validationService->validateDimensions($width, $height);
            $errors = array_merge($errors, $dimensionErrors);
        }
        
        // Validate background
        $background = $design->getBackground();
        if (isset($background['type']) && $background['type'] === 'color') {
            $backgroundColor = $background['color'] ?? null;
            if ($backgroundColor && !$this->validationService->validateColorValue($backgroundColor)) {
                $warnings[] = 'Invalid background color format';
            }
        }
        
        // Validate layers
        $layers = $design->getLayers();
        if ($layers->isEmpty()) {
            $warnings[] = 'Design has no layers';
        } else {
            foreach ($layers as $layer) {
                $layerValidation = $this->validateLayer($layer);
                $errors = array_merge($errors, $layerValidation['errors']);
                $warnings = array_merge($warnings, $layerValidation['warnings']);
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    private function validateLayer(Layer $layer): array
    {
        $errors = [];
        $warnings = [];
        
        // Check if layer type is supported
        if (!$this->findRenderer($layer)) {
            $warnings[] = "Layer type '{$layer->getType()}' is not supported";
        }
        
        // Validate layer dimensions
        $width = $layer->getWidth();
        $height = $layer->getHeight();
        
        if ($width !== null && $width <= 0) {
            $errors[] = "Layer {$layer->getId()} has invalid width";
        }
        
        if ($height !== null && $height <= 0) {
            $errors[] = "Layer {$layer->getId()} has invalid height";
        }
        
        // Validate opacity
        $opacity = $layer->getOpacity();
        if ($opacity !== null && ($opacity < 0 || $opacity > 1)) {
            $errors[] = "Layer {$layer->getId()} has invalid opacity";
        }
        
        // Validate z-index
        $zIndex = $layer->getZIndex();
        if ($zIndex !== null && !is_numeric($zIndex)) {
            $errors[] = "Layer {$layer->getId()} has invalid z-index";
        }
        
        return [
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    
}
