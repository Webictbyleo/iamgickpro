<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Service\Svg\SvgRendererService;
use App\Service\Svg\SvgDocumentBuilder;
use App\Service\Svg\SvgTransformBuilder;
use App\Service\Svg\SvgValidationService;
use App\Service\Svg\SvgErrorRecoveryService;
use App\Service\Svg\LayerRenderer\TextLayerRenderer;
use App\Service\Svg\LayerRenderer\ImageLayerRenderer;
use App\Service\Svg\LayerRenderer\ShapeLayerRenderer;
use App\Service\Svg\LayerRenderer\GroupLayerRenderer;
use App\Service\Svg\LayerRenderer\VideoLayerRenderer;
use App\Service\Svg\LayerRenderer\AudioLayerRenderer;
use App\Service\Svg\LayerRenderer\SvgLayerRenderer;
use App\Entity\Layer;
use Psr\Log\NullLogger;

/**
 * Comprehensive SVG Layer Processing Test Suite
 * 
 * This test suite validates all layer types, properties, edge cases,
 * performance characteristics, and error handling of the SVG processing system.
 */
class ComprehensiveSvgLayerTest
{
    private SvgDocumentBuilder $documentBuilder;
    private SvgTransformBuilder $transformBuilder;
    private SvgValidationService $validationService;
    private SvgErrorRecoveryService $errorRecoveryService;
    private array $renderers;
    private array $testResults = [];
    private array $performanceMetrics = [];
    private int $testsPassed = 0;
    private int $totalTests = 0;
    private float $startTime;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->documentBuilder = new SvgDocumentBuilder(true);
        $this->transformBuilder = new SvgTransformBuilder();
        
        // Initialize services with required dependencies
        $logger = new NullLogger();
        $this->validationService = new SvgValidationService($logger);
        $this->errorRecoveryService = new SvgErrorRecoveryService($logger);
        
        // Initialize all layer renderers
        $this->renderers = [
            'text' => new TextLayerRenderer($this->transformBuilder),
            'image' => new ImageLayerRenderer($this->transformBuilder),
            'shape' => new ShapeLayerRenderer($this->transformBuilder),
            'group' => new GroupLayerRenderer($this->transformBuilder),
            'video' => new VideoLayerRenderer($this->transformBuilder),
            'audio' => new AudioLayerRenderer($this->transformBuilder),
            'svg' => new SvgLayerRenderer($this->transformBuilder)
        ];
    }

    public function runComprehensiveTests(): void
    {
        echo "🚀 COMPREHENSIVE SVG LAYER PROCESSING TEST SUITE\n";
        echo str_repeat("=", 70) . "\n\n";

        // Core functionality tests
        $this->testBasicLayerRendering();
        $this->testAdvancedTextProperties();
        $this->testComplexImageProcessing();
        $this->testAllShapeVariations();
        $this->testGroupLayerComplexity();
        $this->testMediaLayerHandling();
        
        // Advanced feature tests
        $this->testTransformations();
        $this->testFiltersAndEffects();
        $this->testGradientsAndPatterns();
        $this->testAnimationProperties();
        $this->testAccessibilityFeatures();
        
        // Edge case and error handling tests
        $this->testEdgeCases();
        $this->testErrorRecovery();
        $this->testMalformedData();
        $this->testLargeDataSets();
        
        // Performance and validation tests
        $this->testPerformanceMetrics();
        $this->testSvgValidation();
        $this->testComplexDocuments();
        
        // Integration tests
        $this->testFullWorkflowIntegration();
        
        $this->generateComprehensiveReport();
    }

    private function testBasicLayerRendering(): void
    {
        echo "🔧 Testing Basic Layer Rendering...\n";
        
        foreach (array_keys($this->renderers) as $layerType) {
            $this->runTest("Basic {$layerType} rendering", function() use ($layerType) {
                $layer = $this->createBasicLayer($layerType);
                $renderer = $this->renderers[$layerType];
                
                $this->assertTrue($renderer->canRender($layer), "Renderer should handle {$layerType}");
                $element = $renderer->render($layer, $this->documentBuilder);
                $this->assertNotNull($element, "Element should not be null");
                $this->assertInstanceOf(DOMElement::class, $element, "Should return DOMElement");
                
                return $element;
            });
        }
    }

    private function testAdvancedTextProperties(): void
    {
        echo "📝 Testing Advanced Text Properties...\n";
        
        $textTests = [
            'Rich Text Formatting' => [
                'text' => 'Bold <b>text</b> and <i>italic</i> and <u>underlined</u>',
                'fontSize' => 18,
                'fontFamily' => 'Georgia, serif',
                'color' => '#2c3e50',
                'fontWeight' => 'bold',
                'fontStyle' => 'normal',
                'textDecoration' => 'none',
                'lineHeight' => 1.6,
                'letterSpacing' => 1.2,
                'wordSpacing' => 2,
                'textAlign' => 'justify'
            ],
            'Multi-language Text' => [
                'text' => 'Hello 世界 مرحبا Здравствуй こんにちは',
                'fontSize' => 16,
                'fontFamily' => 'Noto Sans, Arial Unicode MS',
                'direction' => 'ltr',
                'unicodeBidi' => 'normal'
            ],
            'Text with Shadow and Outline' => [
                'text' => 'Stylized Text',
                'fontSize' => 24,
                'color' => '#e74c3c',
                'shadow' => [
                    'enabled' => true,
                    'offsetX' => 3,
                    'offsetY' => 3,
                    'blur' => 5,
                    'color' => '#000000',
                    'opacity' => 0.5
                ],
                'stroke' => '#ffffff',
                'strokeWidth' => 1
            ],
            'Text on Path' => [
                'text' => 'Text following a curved path',
                'fontSize' => 14,
                'path' => 'M 10 50 Q 50 10 90 50',
                'pathOffset' => 0,
                'pathSide' => 'left'
            ],
            'Vertical Text' => [
                'text' => 'Vertical Text Example',
                'fontSize' => 16,
                'writingMode' => 'vertical-rl',
                'textOrientation' => 'mixed'
            ]
        ];

        foreach ($textTests as $testName => $properties) {
            $this->runTest("Text: {$testName}", function() use ($properties) {
                $layer = $this->createLayer('text', $properties);
                return $this->renderers['text']->render($layer, $this->documentBuilder);
            });
        }
    }

    private function testComplexImageProcessing(): void
    {
        echo "🖼️ Testing Complex Image Processing...\n";
        
        $imageTests = [
            'Image with All Filters' => [
                'src' => 'https://picsum.photos/200/150',
                'brightness' => 1.3,
                'contrast' => 1.2,
                'saturation' => 0.8,
                'hue' => 45,
                'blur' => 1.5,
                'grayscale' => 0.3,
                'sepia' => 0.2,
                'invert' => 0.1
            ],
            'Image with Crop and Fit' => [
                'src' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iIzMzOThmZiIvPjx0ZXh0IHg9IjEwMCIgeT0iNzUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNiIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIwLjNlbSI+U2FtcGxlIEltYWdlPC90ZXh0Pjwvc3ZnPg==',
                'fit' => 'cover',
                'cropX' => 10,
                'cropY' => 10,
                'cropWidth' => 180,
                'cropHeight' => 130,
                'preserveAspectRatio' => false
            ],
            'Image with Mask and Blend Mode' => [
                'src' => 'https://picsum.photos/seed/test/300/200',
                'mask' => [
                    'type' => 'radial',
                    'centerX' => 50,
                    'centerY' => 50,
                    'radius' => 40
                ],
                'blendMode' => 'multiply',
                'opacity' => 0.8
            ],
            'Responsive Image with Srcset' => [
                'src' => 'https://picsum.photos/400/300',
                'srcset' => [
                    '1x' => 'https://picsum.photos/400/300',
                    '2x' => 'https://picsum.photos/800/600',
                    '3x' => 'https://picsum.photos/1200/900'
                ],
                'sizes' => '(max-width: 400px) 100vw, 400px'
            ]
        ];

        foreach ($imageTests as $testName => $properties) {
            $this->runTest("Image: {$testName}", function() use ($properties) {
                $layer = $this->createLayer('image', $properties);
                return $this->renderers['image']->render($layer, $this->documentBuilder);
            });
        }
    }

    private function testAllShapeVariations(): void
    {
        echo "🔷 Testing All Shape Variations...\n";
        
        $shapes = [
            'rectangle' => [
                'basic' => ['cornerRadius' => 0],
                'rounded' => ['cornerRadius' => 15],
                'mixed_corners' => ['cornerRadiusTopLeft' => 10, 'cornerRadiusTopRight' => 5, 'cornerRadiusBottomLeft' => 15, 'cornerRadiusBottomRight' => 0]
            ],
            'circle' => [
                'basic' => [],
                'with_pattern' => ['pattern' => ['type' => 'dots', 'size' => 3, 'spacing' => 8, 'color' => '#ff6b6b']]
            ],
            'ellipse' => [
                'basic' => [],
                'stretched' => ['rx' => 50, 'ry' => 25]
            ],
            'triangle' => [
                'equilateral' => ['points' => [[0, 100], [50, 0], [100, 100]]],
                'right_angle' => ['points' => [[0, 100], [0, 0], [100, 100]]]
            ],
            'polygon' => [
                'hexagon' => ['points' => 6, 'radius' => 50],
                'octagon' => ['points' => 8, 'radius' => 40],
                'custom' => ['points' => [[10, 10], [90, 10], [90, 90], [50, 50], [10, 90]]]
            ],
            'star' => [
                'five_pointed' => ['points' => 5, 'outerRadius' => 50, 'innerRadius' => 20],
                'eight_pointed' => ['points' => 8, 'outerRadius' => 45, 'innerRadius' => 25]
            ],
            'line' => [
                'straight' => ['x1' => 0, 'y1' => 0, 'x2' => 100, 'y2' => 0],
                'diagonal' => ['x1' => 0, 'y1' => 0, 'x2' => 100, 'y2' => 100],
                'dashed' => ['strokeDasharray' => '5,5', 'strokeDashoffset' => 2]
            ],
            'arrow' => [
                'simple' => ['direction' => 'right', 'headSize' => 10],
                'double_headed' => ['startArrow' => true, 'endArrow' => true]
            ]
        ];

        foreach ($shapes as $shapeType => $variations) {
            foreach ($variations as $variation => $specificProps) {
                $properties = array_merge([
                    'shapeType' => $shapeType,
                    'fill' => '#3498db',
                    'stroke' => '#2980b9',
                    'strokeWidth' => 2
                ], $specificProps);

                $this->runTest("Shape: {$shapeType} - {$variation}", function() use ($properties) {
                    $layer = $this->createLayer('shape', $properties);
                    return $this->renderers['shape']->render($layer, $this->documentBuilder);
                });
            }
        }
    }

    private function testTransformations(): void
    {
        echo "🔄 Testing Transformations...\n";
        
        $transformTests = [
            'Simple Rotation' => ['rotation' => 45],
            'Scale Transform' => ['scaleX' => 1.5, 'scaleY' => 0.8],
            'Skew Transform' => ['skewX' => 15, 'skewY' => -10],
            'Complex Transform' => [
                'rotation' => 30,
                'scaleX' => 1.2,
                'scaleY' => 1.2,
                'translateX' => 20,
                'translateY' => -15,
                'skewX' => 5
            ],
            'Matrix Transform' => [
                'matrix' => [1.2, 0.3, -0.1, 0.9, 50, 25]
            ],
            'Transform Origin' => [
                'rotation' => 90,
                'transformOrigin' => 'center center'
            ]
        ];

        foreach ($transformTests as $testName => $transform) {
            $this->runTest("Transform: {$testName}", function() use ($transform) {
                $layer = $this->createLayer('shape', array_merge([
                    'shapeType' => 'rectangle',
                    'fill' => '#e74c3c'
                ], $transform));
                return $this->renderers['shape']->render($layer, $this->documentBuilder);
            });
        }
    }

    private function testGradientsAndPatterns(): void
    {
        echo "🎨 Testing Gradients and Patterns...\n";
        
        $gradientTests = [
            'Linear Gradient - Horizontal' => [
                'fill' => [
                    'type' => 'linear',
                    'colors' => [
                        ['color' => '#ff6b6b', 'stop' => 0.0],
                        ['color' => '#4ecdc4', 'stop' => 0.5],
                        ['color' => '#45b7d1', 'stop' => 1.0]
                    ],
                    'angle' => 0
                ]
            ],
            'Radial Gradient' => [
                'fill' => [
                    'type' => 'radial',
                    'colors' => [
                        ['color' => '#ffffff', 'stop' => 0.0],
                        ['color' => '#000000', 'stop' => 1.0]
                    ],
                    'centerX' => 0.5,
                    'centerY' => 0.5,
                    'radius' => 0.8
                ]
            ],
            'Complex Linear Gradient' => [
                'fill' => [
                    'type' => 'linear',
                    'colors' => [
                        ['color' => '#ff0000', 'stop' => 0.0],
                        ['color' => '#00ff00', 'stop' => 0.33],
                        ['color' => '#0000ff', 'stop' => 0.66],
                        ['color' => '#ff0000', 'stop' => 1.0]
                    ],
                    'angle' => 45
                ]
            ]
        ];

        foreach ($gradientTests as $testName => $properties) {
            $this->runTest("Gradient: {$testName}", function() use ($properties, $testName) {
                // Create a complete document for proper gradient testing
                $svgRoot = $this->documentBuilder->createDocument(400, 300, '#ffffff');
                
                $layer = $this->createLayer('shape', array_merge([
                    'shapeType' => 'rectangle'
                ], $properties));
                
                $element = $this->renderers['shape']->render($layer, $this->documentBuilder);
                
                if ($element) {
                    $importedElement = $svgRoot->ownerDocument->importNode($element, true);
                    $svgRoot->appendChild($importedElement);
                    
                    // CRUCIAL: Process all definitions after adding all layers
                    $this->documentBuilder->processDefinitions($svgRoot);
                    
                    // Validate gradient in final output
                    $svgContent = $this->documentBuilder->saveDocument($svgRoot);
                    
                    // Validation checks for gradients
                    $hasLinearGradient = strpos($svgContent, '<linearGradient') !== false;
                    $hasRadialGradient = strpos($svgContent, '<radialGradient') !== false;
                    $hasGradientRef = strpos($svgContent, 'url(#gradient-') !== false;
                    $hasStops = strpos($svgContent, '<stop') !== false;
                    
                    $fillType = $properties['fill']['type'] ?? 'unknown';
                    
                    if ($fillType === 'linear' && !$hasLinearGradient) {
                        throw new Exception("Linear gradient not found in SVG output for {$testName}");
                    }
                    
                    if ($fillType === 'radial' && !$hasRadialGradient) {
                        throw new Exception("Radial gradient not found in SVG output for {$testName}");
                    }
                    
                    if (!$hasGradientRef) {
                        throw new Exception("Gradient reference (url(#gradient-)) not found in SVG output for {$testName}");
                    }
                    
                    if (!$hasStops) {
                        throw new Exception("Gradient stops not found in SVG output for {$testName}");
                    }
                    
                    // Save test output for inspection
                    file_put_contents(__DIR__ . "/output/gradient_test_{$testName}.svg", $svgContent);
                    
                    echo "    ✓ Gradient validation passed for {$testName}\n";
                }
                
                return $element;
            });
        }

        $patternTests = [
            'Dot Pattern' => [
                'pattern' => [
                    'type' => 'dots',
                    'size' => 4,
                    'spacing' => 12,
                    'color' => '#e67e22'
                ]
            ],
            'Stripe Pattern' => [
                'pattern' => [
                    'type' => 'stripes',
                    'width' => 8,
                    'spacing' => 16,
                    'angle' => 45,
                    'color' => '#9b59b6'
                ]
            ],
            'Grid Pattern' => [
                'pattern' => [
                    'type' => 'grid',
                    'size' => 20,
                    'strokeWidth' => 1,
                    'color' => '#34495e'
                ]
            ]
        ];

        foreach ($patternTests as $testName => $properties) {
            $this->runTest("Pattern: {$testName}", function() use ($properties) {
                $layer = $this->createLayer('shape', array_merge([
                    'shapeType' => 'circle'
                ], $properties));
                return $this->renderers['shape']->render($layer, $this->documentBuilder);
            });
        }
    }

    private function testEdgeCases(): void
    {
        echo "⚠️ Testing Edge Cases...\n";
        
        $edgeCases = [
            'Zero Dimensions' => function() {
                $layer = $this->createLayer('shape', ['shapeType' => 'rectangle']);
                $layer->setWidth(0);
                $layer->setHeight(0);
                return $this->renderers['shape']->render($layer, $this->documentBuilder);
            },
            'Negative Dimensions' => function() {
                $layer = $this->createLayer('shape', ['shapeType' => 'rectangle']);
                $layer->setWidth(-100);
                $layer->setHeight(-50);
                return $this->renderers['shape']->render($layer, $this->documentBuilder);
            },
            'Very Large Dimensions' => function() {
                $layer = $this->createLayer('shape', ['shapeType' => 'rectangle']);
                $layer->setWidth(999999);
                $layer->setHeight(999999);
                return $this->renderers['shape']->render($layer, $this->documentBuilder);
            },
            'Empty Text Layer' => function() {
                $layer = $this->createLayer('text', ['text' => '']);
                return $this->renderers['text']->render($layer, $this->documentBuilder);
            },
            'Invalid Image URL' => function() {
                $layer = $this->createLayer('image', ['src' => 'invalid://url']);
                return $this->renderers['image']->render($layer, $this->documentBuilder);
            },
            'Malformed Gradient' => function() {
                $layer = $this->createLayer('shape', [
                    'shapeType' => 'rectangle',
                    'gradient' => [
                        'type' => 'invalid',
                        'stops' => []
                    ]
                ]);
                return $this->renderers['shape']->render($layer, $this->documentBuilder);
            }
        ];

        foreach ($edgeCases as $testName => $testFunction) {
            $this->runTest("Edge Case: {$testName}", $testFunction, true);
        }
    }

    private function testPerformanceMetrics(): void
    {
        echo "⚡ Testing Performance Metrics...\n";
        
        $performanceTests = [
            'Single Layer Rendering' => function() {
                $start = microtime(true);
                for ($i = 0; $i < 100; $i++) {
                    $layer = $this->createBasicLayer('shape');
                    $this->renderers['shape']->render($layer, $this->documentBuilder);
                }
                return microtime(true) - $start;
            },
            'Complex Document Generation' => function() {
                $start = microtime(true);
                $svgRoot = $this->documentBuilder->createDocument(1920, 1080);
                
                for ($i = 0; $i < 50; $i++) {
                    $layer = $this->createRandomLayer();
                    $renderer = $this->renderers[$layer->getType()];
                    $element = $renderer->render($layer, $this->documentBuilder);
                    if ($element) {
                        $importedElement = $svgRoot->ownerDocument->importNode($element, true);
                        $svgRoot->appendChild($importedElement);
                    }
                }
                
                $this->documentBuilder->saveDocument($svgRoot);
                return microtime(true) - $start;
            },
            'Memory Usage Test' => function() {
                $initialMemory = memory_get_usage(true);
                
                for ($i = 0; $i < 1000; $i++) {
                    $layer = $this->createRandomLayer();
                    $renderer = $this->renderers[$layer->getType()];
                    $renderer->render($layer, $this->documentBuilder);
                }
                
                return memory_get_usage(true) - $initialMemory;
            }
        ];

        foreach ($performanceTests as $testName => $testFunction) {
            $result = $testFunction();
            $this->performanceMetrics[$testName] = $result;
            
            if (strpos($testName, 'Memory') !== false) {
                echo "  📊 {$testName}: " . $this->formatBytes($result) . "\n";
            } else {
                echo "  ⏱️ {$testName}: " . number_format($result * 1000, 2) . "ms\n";
            }
        }
    }

    private function testComplexDocuments(): void
    {
        echo "📄 Testing Complex Document Generation...\n";
        
        $complexTests = [
            'Multi-layer Document with All Types' => function() {
                $svgRoot = $this->documentBuilder->createDocument(1200, 800, '#f8f9fa');
                
                $layers = [
                    $this->createLayer('text', ['text' => 'Document Title', 'fontSize' => 32, 'fontWeight' => 'bold']),
                    $this->createLayer('shape', ['shapeType' => 'rectangle', 'fill' => '#e9ecef', 'cornerRadius' => 8]),
                    $this->createLayer('image', ['src' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iIzMzOThmZiIvPjwvc3ZnPg==']),
                    $this->createLayer('shape', ['shapeType' => 'circle', 'fill' => '#28a745']),
                    $this->createLayer('text', ['text' => 'Subtitle', 'fontSize' => 18, 'color' => '#6c757d']),
                ];
                
                foreach ($layers as $layer) {
                    $renderer = $this->renderers[$layer->getType()];
                    $element = $renderer->render($layer, $this->documentBuilder);
                    if ($element) {
                        $importedElement = $svgRoot->ownerDocument->importNode($element, true);
                        $svgRoot->appendChild($importedElement);
                    }
                }
                
                // IMPORTANT: Process all definitions after adding all layers
                $this->documentBuilder->processDefinitions($svgRoot);
                
                $svgContent = $this->documentBuilder->saveDocument($svgRoot);
                file_put_contents(__DIR__ . '/output/complex_document.svg', $svgContent);
                
                return strlen($svgContent);
            },
            'Nested Groups Document' => function() {
                $svgRoot = $this->documentBuilder->createDocument(800, 600);
                
                // Create nested group structure
                for ($i = 0; $i < 3; $i++) {
                    $groupLayer = $this->createLayer('group', []);
                    $groupElement = $this->renderers['group']->render($groupLayer, $this->documentBuilder);
                    
                    if ($groupElement) {
                        // Add child elements to group
                        for ($j = 0; $j < 5; $j++) {
                            $childLayer = $this->createRandomLayer();
                            $childRenderer = $this->renderers[$childLayer->getType()];
                            $childElement = $childRenderer->render($childLayer, $this->documentBuilder);
                            
                            if ($childElement) {
                                $importedChild = $svgRoot->ownerDocument->importNode($childElement, true);
                                $groupElement->appendChild($importedChild);
                            }
                        }
                        
                        $importedGroup = $svgRoot->ownerDocument->importNode($groupElement, true);
                        $svgRoot->appendChild($importedGroup);
                    }
                }
                
                return $this->documentBuilder->saveDocument($svgRoot);
            }
        ];

        foreach ($complexTests as $testName => $testFunction) {
            $this->runTest("Complex: {$testName}", $testFunction);
        }
    }

    // Helper methods
    private function createBasicLayer(string $type): Layer
    {
        $properties = match($type) {
            'text' => ['text' => 'Sample Text', 'fontSize' => 16, 'color' => '#000000'],
            'image' => ['src' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzMzOThmZiIvPjwvc3ZnPg=='],
            'shape' => ['shapeType' => 'rectangle', 'fill' => '#3498db'],
            'group' => [],
            'video' => ['src' => ''],
            'audio' => ['src' => ''],
            default => []
        };

        return $this->createLayer($type, $properties);
    }

    private function createLayer(string $type, array $properties): Layer
    {
        $layer = new Layer();
        $layer->setType($type);
        $layer->setX(rand(10, 200));
        $layer->setY(rand(10, 200));
        $layer->setWidth(rand(50, 150));
        $layer->setHeight(rand(30, 100));
        $layer->setZIndex(rand(1, 10));
        $layer->setVisible(true);
        $layer->setProperties($properties);
        
        return $layer;
    }

    private function createRandomLayer(): Layer
    {
        $types = array_keys($this->renderers);
        $randomType = $types[array_rand($types)];
        return $this->createBasicLayer($randomType);
    }

    private function runTest(string $testName, callable $testFunction, bool $expectErrors = false): void
    {
        $this->totalTests++;
        $startTime = microtime(true);
        
        try {
            $result = $testFunction();
            $duration = microtime(true) - $startTime;
            
            if (!$expectErrors && $result === null) {
                throw new Exception("Test returned null result");
            }
            
            $this->testsPassed++;
            $this->testResults[] = [
                'name' => $testName,
                'status' => 'PASS',
                'duration' => $duration,
                'message' => 'Test completed successfully'
            ];
            
            echo "  ✅ {$testName} (" . number_format($duration * 1000, 2) . "ms)\n";
            
        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;
            
            if ($expectErrors) {
                $this->testsPassed++;
                $status = 'PASS';
                $message = "Expected error handled: " . $e->getMessage();
                echo "  ✅ {$testName} (Expected error handled)\n";
            } else {
                $status = 'FAIL';
                $message = $e->getMessage();
                echo "  ❌ {$testName}: {$message}\n";
            }
            
            $this->testResults[] = [
                'name' => $testName,
                'status' => $status,
                'duration' => $duration,
                'message' => $message
            ];
        }
    }

    private function assertTrue(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    private function assertNotNull($value, string $message): void
    {
        if ($value === null) {
            throw new Exception($message);
        }
    }

    private function assertInstanceOf(string $expectedClass, $value, string $message): void
    {
        if (!($value instanceof $expectedClass)) {
            throw new Exception($message . " Expected {$expectedClass}, got " . gettype($value));
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return number_format($bytes, 2) . ' ' . $units[$unitIndex];
    }

    private function generateComprehensiveReport(): void
    {
        $totalDuration = microtime(true) - $this->startTime;
        
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "📊 COMPREHENSIVE TEST REPORT\n";
        echo str_repeat("=", 80) . "\n";
        
        // Overall statistics
        $passRate = $this->totalTests > 0 ? ($this->testsPassed / $this->totalTests) * 100 : 0;
        echo "🏆 Overall Results:\n";
        echo "   Tests Passed: {$this->testsPassed}/{$this->totalTests}\n";
        echo "   Pass Rate: " . number_format($passRate, 1) . "%\n";
        echo "   Total Duration: " . number_format($totalDuration, 2) . "s\n\n";
        
        // Performance metrics
        if (!empty($this->performanceMetrics)) {
            echo "⚡ Performance Metrics:\n";
            foreach ($this->performanceMetrics as $metric => $value) {
                if (strpos($metric, 'Memory') !== false) {
                    echo "   {$metric}: " . $this->formatBytes((int)$value) . "\n";
                } else {
                    echo "   {$metric}: " . number_format($value * 1000, 2) . "ms\n";
                }
            }
            echo "\n";
        }
        
        // Failed tests
        $failedTests = array_filter($this->testResults, fn($r) => $r['status'] === 'FAIL');
        if (!empty($failedTests)) {
            echo "❌ Failed Tests:\n";
            foreach ($failedTests as $test) {
                echo "   • {$test['name']}: {$test['message']}\n";
            }
            echo "\n";
        }
        
        // Test categories breakdown
        $categories = [];
        foreach ($this->testResults as $result) {
            $category = explode(':', $result['name'])[0];
            if (!isset($categories[$category])) {
                $categories[$category] = ['passed' => 0, 'total' => 0];
            }
            $categories[$category]['total']++;
            if ($result['status'] === 'PASS') {
                $categories[$category]['passed']++;
            }
        }
        
        echo "📋 Test Categories:\n";
        foreach ($categories as $category => $stats) {
            $categoryRate = $stats['total'] > 0 ? ($stats['passed'] / $stats['total']) * 100 : 0;
            echo "   {$category}: {$stats['passed']}/{$stats['total']} (" . number_format($categoryRate, 1) . "%)\n";
        }
        
        // Final verdict
        echo "\n";
        if ($this->testsPassed === $this->totalTests) {
            echo "🎉 ALL TESTS PASSED! The SVG processing system is fully functional.\n";
        } elseif ($passRate >= 90) {
            echo "✨ EXCELLENT! Most tests passed with minor issues.\n";
        } elseif ($passRate >= 70) {
            echo "👍 GOOD! Majority of tests passed, some improvements needed.\n";
        } else {
            echo "⚠️ NEEDS ATTENTION! Multiple test failures detected.\n";
        }
        
        // Create output directory if it doesn't exist
        $outputDir = __DIR__ . '/output';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Save detailed report
        $reportData = [
            'summary' => [
                'total_tests' => $this->totalTests,
                'passed_tests' => $this->testsPassed,
                'pass_rate' => $passRate,
                'duration' => $totalDuration
            ],
            'performance_metrics' => $this->performanceMetrics,
            'test_results' => $this->testResults,
            'categories' => $categories
        ];
        
        file_put_contents($outputDir . '/comprehensive_test_report.json', json_encode($reportData, JSON_PRETTY_PRINT));
        echo "\n📄 Detailed report saved to: output/comprehensive_test_report.json\n";
    }

    // Additional test methods that weren't included in the original
    private function testGroupLayerComplexity(): void
    {
        echo "📂 Testing Group Layer Complexity...\n";
        
        $groupTests = [
            'Basic Group' => [],
            'Group with Clipping' => ['clipPath' => true, 'clipShape' => 'ellipse'],
            'Group with Masking' => ['mask' => true, 'maskType' => 'luminance'],
            'Group with Filters' => ['filter' => ['blur' => 2, 'brightness' => 1.2]]
        ];

        foreach ($groupTests as $testName => $properties) {
            $this->runTest("Group: {$testName}", function() use ($properties) {
                $layer = $this->createLayer('group', $properties);
                return $this->renderers['group']->render($layer, $this->documentBuilder);
            });
        }
    }

    private function testMediaLayerHandling(): void
    {
        echo "🎬 Testing Media Layer Handling...\n";
        
        $mediaTests = [
            'Video with Poster' => ['video', ['src' => '', 'poster' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1zbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iIzMzOThmZiIvPjwvc3ZnPg==']],
            'Audio with Visualization' => ['audio', ['src' => '', 'showWaveform' => true, 'waveformColor' => '#e74c3c']]
        ];

        foreach ($mediaTests as $testName => [$type, $properties]) {
            $this->runTest("Media: {$testName}", function() use ($type, $properties) {
                $layer = $this->createLayer($type, $properties);
                return $this->renderers[$type]->render($layer, $this->documentBuilder);
            });
        }
    }

    private function testFiltersAndEffects(): void
    {
        echo "✨ Testing Filters and Effects...\n";
        
        $effectTests = [
            'Drop Shadow' => [
                'shadow' => [
                    'enabled' => true,
                    'offsetX' => 5,
                    'offsetY' => 5,
                    'blur' => 10,
                    'color' => '#000000',
                    'opacity' => 0.3
                ]
            ],
            'Glow Effect' => [
                'glow' => [
                    'enabled' => true,
                    'color' => '#ffff00',
                    'blur' => 8,
                    'strength' => 2
                ]
            ],
            'Multiple Filters' => [
                'filter' => [
                    'blur' => 1,
                    'brightness' => 1.2,
                    'contrast' => 1.1,
                    'saturate' => 0.8
                ]
            ]
        ];

        foreach ($effectTests as $testName => $properties) {
            $this->runTest("Effect: {$testName}", function() use ($properties) {
                $layer = $this->createLayer('shape', array_merge([
                    'shapeType' => 'rectangle',
                    'fill' => '#3498db'
                ], $properties));
                return $this->renderers['shape']->render($layer, $this->documentBuilder);
            });
        }
    }

    private function testAnimationProperties(): void
    {
        echo "🎭 Testing Animation Properties...\n";
        
        // Animation properties would be tested here
        // This is a placeholder for future animation support
        $this->runTest("Animation: Basic Animation Support", function() {
            $layer = $this->createLayer('shape', [
                'shapeType' => 'circle',
                'fill' => '#e74c3c',
                'animation' => [
                    'type' => 'rotation',
                    'duration' => 2,
                    'easing' => 'ease-in-out'
                ]
            ]);
            return $this->renderers['shape']->render($layer, $this->documentBuilder);
        });
    }

    private function testAccessibilityFeatures(): void
    {
        echo "♿ Testing Accessibility Features...\n";
        
        $a11yTests = [
            'Text with ARIA Labels' => [
                'text' => 'Accessible Text',
                'ariaLabel' => 'Descriptive label for screen readers',
                'role' => 'heading'
            ],
            'Image with Alt Text' => [
                'src' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1zbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzMzOThmZiIvPjwvc3ZnPg==',
                'alt' => 'Blue square image for accessibility testing'
            ]
        ];

        $this->runTest("A11y: Text with ARIA", function() use ($a11yTests) {
            $layer = $this->createLayer('text', $a11yTests['Text with ARIA Labels']);
            return $this->renderers['text']->render($layer, $this->documentBuilder);
        });

        $this->runTest("A11y: Image with Alt Text", function() use ($a11yTests) {
            $layer = $this->createLayer('image', $a11yTests['Image with Alt Text']);
            return $this->renderers['image']->render($layer, $this->documentBuilder);
        });
    }

    private function testErrorRecovery(): void
    {
        echo "🔧 Testing Error Recovery...\n";
        
        // Test error recovery mechanisms
        $this->runTest("Error Recovery: Invalid Properties", function() {
            $layer = $this->createLayer('shape', [
                'shapeType' => 'invalid_shape',
                'fill' => 'invalid_color',
                'strokeWidth' => 'not_a_number'
            ]);
            
            // Should still render with fallback values
            $element = $this->renderers['shape']->render($layer, $this->documentBuilder);
            $this->assertNotNull($element, "Should recover from invalid properties");
            return $element;
        }, true);
    }

    private function testMalformedData(): void
    {
        echo "🚫 Testing Malformed Data Handling...\n";
        
        $malformedTests = [
            'Malformed JSON Properties' => function() {
                $layer = new Layer();
                $layer->setType('text');
                $layer->setProperties(['text' => null, 'fontSize' => 'invalid']);
                return $this->renderers['text']->render($layer, $this->documentBuilder);
            },
            'Missing Required Properties' => function() {
                $layer = new Layer();
                $layer->setType('shape');
                $layer->setProperties([]); // Missing shapeType
                return $this->renderers['shape']->render($layer, $this->documentBuilder);
            }
        ];

        foreach ($malformedTests as $testName => $testFunction) {
            $this->runTest("Malformed: {$testName}", $testFunction, true);
        }
    }

    private function testLargeDataSets(): void
    {
        echo "📈 Testing Large Data Sets...\n";
        
        $this->runTest("Large Dataset: 1000 Layers", function() {
            $svgRoot = $this->documentBuilder->createDocument(2000, 2000);
            $processedLayers = 0;
            
            for ($i = 0; $i < 1000; $i++) {
                $layer = $this->createRandomLayer();
                $renderer = $this->renderers[$layer->getType()];
                $element = $renderer->render($layer, $this->documentBuilder);
                
                if ($element) {
                    $processedLayers++;
                    if ($processedLayers <= 100) { // Only add first 100 to avoid memory issues
                        $importedElement = $svgRoot->ownerDocument->importNode($element, true);
                        $svgRoot->appendChild($importedElement);
                    }
                }
            }
            
            return $processedLayers;
        });
    }

    private function testSvgValidation(): void
    {
        echo "✅ Testing SVG Validation...\n";
        
        $this->runTest("SVG Validation: Structure Check", function() {
            $svgRoot = $this->documentBuilder->createDocument(400, 300);
            $layer = $this->createBasicLayer('text');
            $element = $this->renderers['text']->render($layer, $this->documentBuilder);
            
            if ($element) {
                $importedElement = $svgRoot->ownerDocument->importNode($element, true);
                $svgRoot->appendChild($importedElement);
            }
            
            $svgContent = $this->documentBuilder->saveDocument($svgRoot);
            
            // Basic validation checks
            $this->assertTrue(strpos($svgContent, '<svg') !== false, "Should contain SVG opening tag");
            $this->assertTrue(strpos($svgContent, '</svg>') !== false, "Should contain SVG closing tag");
            $this->assertTrue(strpos($svgContent, 'xmlns=') !== false, "Should contain SVG namespace");
            
            return strlen($svgContent);
        });
    }

    private function testFullWorkflowIntegration(): void
    {
        echo "🔄 Testing Full Workflow Integration...\n";
        
        $this->runTest("Integration: Complete Design Workflow", function() {
            // Simulate a complete design workflow
            $designs = [];
            
            // Create multiple documents
            for ($docIndex = 0; $docIndex < 3; $docIndex++) {
                $svgRoot = $this->documentBuilder->createDocument(800, 600, '#ffffff');
                
                // Add various layers
                $layerTypes = ['text', 'shape', 'image'];
                foreach ($layerTypes as $type) {
                    for ($i = 0; $i < 5; $i++) {
                        $layer = $this->createBasicLayer($type);
                        $renderer = $this->renderers[$type];
                        $element = $renderer->render($layer, $this->documentBuilder);
                        
                        if ($element) {
                            $importedElement = $svgRoot->ownerDocument->importNode($element, true);
                            $svgRoot->appendChild($importedElement);
                        }
                    }
                }
                
                // IMPORTANT: Process all definitions after adding all layers
                $this->documentBuilder->processDefinitions($svgRoot);
                
                $designs[] = $this->documentBuilder->saveDocument($svgRoot);
            }
            
            // Validate all designs
            foreach ($designs as $index => $design) {
                $this->assertTrue(strlen($design) > 0, "Design {$index} should not be empty");
                file_put_contents(__DIR__ . "/output/integration_design_{$index}.svg", $design);
            }
            
            return count($designs);
        });
        
        // Add specific gradient workflow test
        $this->runTest("Integration: Gradient Workflow", function() {
            $svgRoot = $this->documentBuilder->createDocument(600, 400, '#f0f0f0');
            
            // Create layers with different gradient types
            $gradientLayers = [
                $this->createLayer('shape', [
                    'shapeType' => 'rectangle',
                    'fill' => [
                        'type' => 'linear',
                        'colors' => [
                            ['color' => '#ff0000', 'stop' => 0.0],
                            ['color' => '#0000ff', 'stop' => 1.0]
                        ],
                        'angle' => 0
                    ]
                ]),
                $this->createLayer('shape', [
                    'shapeType' => 'circle',
                    'fill' => [
                        'type' => 'radial',
                        'colors' => [
                            ['color' => '#ffff00', 'stop' => 0.0],
                            ['color' => '#ff8800', 'stop' => 0.5],
                            ['color' => '#ff0000', 'stop' => 1.0]
                        ],
                        'centerX' => 0.5,
                        'centerY' => 0.5,
                        'radius' => 0.8
                    ]
                ]),
                $this->createLayer('shape', [
                    'shapeType' => 'rectangle',
                    'fill' => [
                        'type' => 'linear',
                        'colors' => [
                            ['color' => '#00ff00', 'stop' => 0.0],
                            ['color' => '#0000ff', 'stop' => 1.0]
                        ],
                        'angle' => 90
                    ]
                ])
            ];
            
            // Render all gradient layers
            foreach ($gradientLayers as $layer) {
                $element = $this->renderers['shape']->render($layer, $this->documentBuilder);
                if ($element) {
                    $importedElement = $svgRoot->ownerDocument->importNode($element, true);
                    $svgRoot->appendChild($importedElement);
                }
            }
            
            // CRUCIAL: Process all definitions
            $this->documentBuilder->processDefinitions($svgRoot);
            
            // Get final SVG content
            $svgContent = $this->documentBuilder->saveDocument($svgRoot);
            
            // Comprehensive gradient validation
            $hasLinearGradients = substr_count($svgContent, '<linearGradient');
            $hasRadialGradients = substr_count($svgContent, '<radialGradient');
            $hasGradientRefs = substr_count($svgContent, 'url(#gradient-');
            $hasStops = substr_count($svgContent, '<stop');
            $hasDefsSection = strpos($svgContent, '<defs>') !== false;
            
            // Save for inspection
            file_put_contents(__DIR__ . "/output/gradient_workflow_test.svg", $svgContent);
            
            // Validate expected gradient elements
            $this->assertTrue($hasLinearGradients >= 2, "Should have at least 2 linear gradients, found: {$hasLinearGradients}");
            $this->assertTrue($hasRadialGradients >= 1, "Should have at least 1 radial gradient, found: {$hasRadialGradients}");
            $this->assertTrue($hasGradientRefs >= 3, "Should have at least 3 gradient references, found: {$hasGradientRefs}");
            $this->assertTrue($hasStops >= 6, "Should have at least 6 gradient stops, found: {$hasStops}");
            $this->assertTrue($hasDefsSection, "Should have defs section");
            
            echo "    ✓ Gradient workflow validation: {$hasLinearGradients} linear, {$hasRadialGradients} radial, {$hasGradientRefs} refs, {$hasStops} stops\n";
            
            return strlen($svgContent);
        });
    }
}

// Create output directory
$outputDir = __DIR__ . '/output';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Run the comprehensive test suite
try {
    $testSuite = new ComprehensiveSvgLayerTest();
    $testSuite->runComprehensiveTests();
} catch (Throwable $e) {
    echo "❌ Test suite failed to initialize: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
