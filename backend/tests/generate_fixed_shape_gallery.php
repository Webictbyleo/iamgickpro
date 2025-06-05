<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Service\Svg\SvgDocumentBuilder;
use App\Service\Svg\SvgTransformBuilder;
use App\Service\Svg\LayerRenderer\ShapeLayerRenderer;
use App\Entity\Layer;

/**
 * Generate a properly structured shape gallery with corrected ID handling
 */
class FixedShapeGalleryGenerator
{
    private SvgDocumentBuilder $documentBuilder;
    private SvgTransformBuilder $transformBuilder;
    private ShapeLayerRenderer $renderer;
    private int $layerIdCounter = 1;

    public function __construct()
    {
        $this->documentBuilder = new SvgDocumentBuilder(true);
        $this->transformBuilder = new SvgTransformBuilder();
        $this->renderer = new ShapeLayerRenderer($this->transformBuilder);
    }

    public function generateFixedGallery(): void
    {
        echo "🎨 Generating Fixed Shape Gallery...\n";

        // Create the main SVG document
        $svgRoot = $this->documentBuilder->createDocument(1200, 800);
        $document = $svgRoot->ownerDocument;

        // Add background gradient
        $this->addBackgroundGradient($document, $svgRoot);

        // Add title
        $this->addTitle($document, $svgRoot);

        // Generate all shape types with proper IDs
        $shapes = $this->getShapeConfigurations();
        
        foreach ($shapes as $shapeConfig) {
            $layer = $this->createLayerWithId($shapeConfig);
            $element = $this->renderer->render($layer, $this->documentBuilder);
            
            if ($element) {
                $importedElement = $document->importNode($element, true);
                $svgRoot->appendChild($importedElement);
            }
        }

        // CRITICAL: Process all definitions AFTER adding all elements
        // This consolidates all <defs> sections and ensures all gradients are properly defined
        $this->documentBuilder->processDefinitions($svgRoot);

        // Save the corrected SVG
        $svgContent = $this->documentBuilder->saveDocument($svgRoot);
        $outputPath = __DIR__ . '/output/fixed_comprehensive_shape_gallery.svg';
        file_put_contents($outputPath, $svgContent);

        // Validate the output
        $this->validateGeneratedSvg($svgContent, $outputPath);
    }

    private function createLayerWithId(array $config): Layer
    {
        $layer = new Layer();
        $layer->setType('shape');
        $layer->setName($config['name'] ?? 'Shape');
        $layer->setX($config['x']);
        $layer->setY($config['y']);
        $layer->setWidth($config['width'] ?? 120);
        $layer->setHeight($config['height'] ?? 120);
        $layer->setZIndex(1);
        $layer->setVisible(true);

        // Use reflection to set the ID (same approach as image layer test)
        $reflection = new \ReflectionClass($layer);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($layer, $this->layerIdCounter++);

        // Set shape properties
        $layer->setProperties($config['properties']);

        return $layer;
    }

    private function getShapeConfigurations(): array
    {
        return [
            // Row 1: Basic shapes
            [
                'name' => 'Rectangle',
                'x' => 50, 'y' => 50,
                'properties' => [
                    'shapeType' => 'rectangle',
                    'fill' => ['type' => 'solid', 'color' => '#ff6b6b']
                ]
            ],
            [
                'name' => 'Circle',
                'x' => 200, 'y' => 50,
                'properties' => [
                    'shapeType' => 'circle',
                    'fill' => ['type' => 'solid', 'color' => '#4ecdc4']
                ]
            ],
            [
                'name' => 'Ellipse',
                'x' => 350, 'y' => 50,
                'properties' => [
                    'shapeType' => 'ellipse',
                    'fill' => ['type' => 'solid', 'color' => '#45b7d1']
                ]
            ],
            [
                'name' => 'Triangle',
                'x' => 500, 'y' => 50,
                'properties' => [
                    'shapeType' => 'triangle',
                    'fill' => ['type' => 'solid', 'color' => '#f9ca24']
                ]
            ],
            [
                'name' => 'Hexagon',
                'x' => 650, 'y' => 50,
                'properties' => [
                    'shapeType' => 'polygon',
                    'sides' => 6,
                    'fill' => ['type' => 'solid', 'color' => '#f0932b']
                ]
            ],

            // Row 2: Advanced shapes
            [
                'name' => 'Star',
                'x' => 50, 'y' => 200,
                'properties' => [
                    'shapeType' => 'star',
                    'numPoints' => 5,
                    'outerRadius' => 60,
                    'innerRadius' => 25,
                    'fill' => ['type' => 'solid', 'color' => '#eb4d4b']
                ]
            ],
            [
                'name' => 'Line',
                'x' => 200, 'y' => 200,
                'properties' => [
                    'shapeType' => 'line',
                    'fill' => ['type' => 'solid', 'color' => 'transparent'],
                    'stroke' => '#6c5ce7',
                    'strokeWidth' => 8
                ]
            ],
            [
                'name' => 'Arrow',
                'x' => 350, 'y' => 200,
                'properties' => [
                    'shapeType' => 'arrow',
                    'fill' => ['type' => 'solid', 'color' => '#a29bfe'],
                    'arrowHeadSize' => 24
                ]
            ],

            // Row 3: Gradient shapes
            [
                'name' => 'Linear Gradient Rectangle',
                'x' => 50, 'y' => 350,
                'properties' => [
                    'shapeType' => 'rectangle',
                    'cornerRadius' => 20,
                    'fill' => [
                        'type' => 'linear',
                        'colors' => [
                            ['color' => '#ff9a9e', 'stop' => 0.0],
                            ['color' => '#fecfef', 'stop' => 0.5],
                            ['color' => '#fecfef', 'stop' => 1.0]
                        ],
                        'angle' => 45
                    ]
                ]
            ],
            [
                'name' => 'Radial Gradient Circle',
                'x' => 200, 'y' => 350,
                'properties' => [
                    'shapeType' => 'circle',
                    'fill' => [
                        'type' => 'radial',
                        'colors' => [
                            ['color' => '#ffecd2', 'stop' => 0.0],
                            ['color' => '#fcb69f', 'stop' => 1.0]
                        ],
                        'centerX' => 0.3,
                        'centerY' => 0.3,
                        'radius' => 0.8
                    ]
                ]
            ],

            // Row 4: Effects shapes
            [
                'name' => 'Shadow Star',
                'x' => 350, 'y' => 350,
                'properties' => [
                    'shapeType' => 'star',
                    'numPoints' => 8,
                    'outerRadius' => 60,
                    'innerRadius' => 30,
                    'fill' => ['type' => 'solid', 'color' => '#feca57'],
                    'stroke' => '#ff6348',
                    'strokeWidth' => 3,
                    'shadow' => [
                        'enabled' => true,
                        'offsetX' => 5,
                        'offsetY' => 5,
                        'blur' => 10,
                        'color' => '#000000',
                        'opacity' => 0.5
                    ]
                ]
            ],
            [
                'name' => 'Glow Octagon',
                'x' => 500, 'y' => 350,
                'properties' => [
                    'shapeType' => 'polygon',
                    'sides' => 8,
                    'fill' => ['type' => 'solid', 'color' => '#48dbfb'],
                    'glow' => [
                        'enabled' => true,
                        'blur' => 15,
                        'color' => '#0abde3',
                        'opacity' => 0.8
                    ]
                ]
            ],

            // Row 5: Complex strokes
            [
                'name' => 'Dashed Rectangle',
                'x' => 50, 'y' => 500,
                'properties' => [
                    'shapeType' => 'rectangle',
                    'fill' => ['type' => 'solid', 'color' => 'transparent'],
                    'stroke' => '#2ed573',
                    'strokeWidth' => 4,
                    'strokeDasharray' => '10,5',
                    'cornerRadius' => 10
                ]
            ],
            [
                'name' => 'Dotted Circle',
                'x' => 200, 'y' => 500,
                'properties' => [
                    'shapeType' => 'circle',
                    'fill' => ['type' => 'solid', 'color' => '#ff4757'],
                    'stroke' => '#ffffff',
                    'strokeWidth' => 3,
                    'strokeDasharray' => '2,8'
                ]
            ]
        ];
    }

    private function addBackgroundGradient(\DOMDocument $document, \DOMElement $svgRoot): void
    {
        // Find or create defs element
        $defs = $document->createElement('defs');
        $svgRoot->insertBefore($defs, $svgRoot->firstChild);

        // Create background gradient
        $gradient = $document->createElement('linearGradient');
        $gradient->setAttribute('id', 'bg-gradient');
        $gradient->setAttribute('x1', '0%');
        $gradient->setAttribute('y1', '0%');
        $gradient->setAttribute('x2', '100%');
        $gradient->setAttribute('y2', '100%');

        $stop1 = $document->createElement('stop');
        $stop1->setAttribute('offset', '0%');
        $stop1->setAttribute('stop-color', '#667eea');
        $gradient->appendChild($stop1);

        $stop2 = $document->createElement('stop');
        $stop2->setAttribute('offset', '100%');
        $stop2->setAttribute('stop-color', '#764ba2');
        $gradient->appendChild($stop2);

        $defs->appendChild($gradient);

        // Add background rectangle
        $bgRect = $document->createElement('rect');
        $bgRect->setAttribute('width', '100%');
        $bgRect->setAttribute('height', '100%');
        $bgRect->setAttribute('fill', 'url(#bg-gradient)');
        $svgRoot->appendChild($bgRect);
    }

    private function addTitle(\DOMDocument $document, \DOMElement $svgRoot): void
    {
        $text = $document->createElement('text');
        $text->setAttribute('x', '600');
        $text->setAttribute('y', '40');
        $text->setAttribute('text-anchor', 'middle');
        $text->setAttribute('font-family', 'Arial, sans-serif');
        $text->setAttribute('font-size', '28');
        $text->setAttribute('font-weight', 'bold');
        $text->setAttribute('fill', 'white');
        $text->setAttribute('stroke', 'rgba(0,0,0,0.3)');
        $text->setAttribute('stroke-width', '1');
        $text->textContent = 'Fixed Comprehensive Shape Gallery';
        $svgRoot->appendChild($text);
    }

    private function validateGeneratedSvg(string $svgContent, string $outputPath): void
    {
        echo "\n📊 SVG Validation Results:\n";
        echo "   📁 File saved to: {$outputPath}\n";
        echo "   📏 File size: " . number_format(strlen($svgContent)) . " bytes\n";

        // Check for structural issues
        $defsCount = substr_count($svgContent, '<defs>');
        echo "   📋 <defs> sections: {$defsCount}\n";

        // Check for gradient references vs definitions
        preg_match_all('/url\(#([^)]+)\)/', $svgContent, $gradientRefs);
        preg_match_all('/id="([^"]*gradient[^"]*)"/', $svgContent, $gradientDefs);
        
        $referencedGradients = array_unique($gradientRefs[1]);
        $definedGradients = array_unique($gradientDefs[1]);
        
        echo "   🎨 Gradient references: " . count($referencedGradients) . "\n";
        echo "   🎨 Gradient definitions: " . count($definedGradients) . "\n";

        // Check for missing definitions
        $missingGradients = array_diff($referencedGradients, $definedGradients);
        if (!empty($missingGradients)) {
            echo "   ❌ Missing gradient definitions: " . implode(', ', $missingGradients) . "\n";
        } else {
            echo "   ✅ All gradients properly defined\n";
        }

        // Check for empty layer IDs
        $emptyIdCount = substr_count($svgContent, 'id="layer-"');
        echo "   🏷️ Empty layer IDs: {$emptyIdCount}\n";

        // Check for layer data attributes
        $layerDataCount = substr_count($svgContent, 'data-layer-id=');
        echo "   🏷️ Layer data attributes: {$layerDataCount}\n";

        // Final validation
        $isValid = $defsCount === 1 && empty($missingGradients) && $emptyIdCount === 0;
        echo "\n" . ($isValid ? "✅ SVG STRUCTURE IS VALID!" : "❌ SVG HAS STRUCTURAL ISSUES") . "\n";
    }
}

// Run the generator
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $generator = new FixedShapeGalleryGenerator();
    $generator->generateFixedGallery();
}
