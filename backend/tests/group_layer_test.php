<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entity\Layer;
use App\Service\Svg\LayerRenderer\GroupLayerRenderer;
use App\Service\Svg\SvgDocumentBuilder;
use App\Service\Svg\SvgTransformBuilder;

// =============================================================================
// GROUP LAYER RENDERER TEST SUITE
// =============================================================================

echo "👥 GROUP LAYER RENDERER TEST SUITE\n";
echo "===================================\n\n";

// Initialize test components
$transformBuilder = new SvgTransformBuilder();
$renderer = new GroupLayerRenderer($transformBuilder);
$builder = new SvgDocumentBuilder();

// Test counters
$totalTests = 0;
$passedTests = 0;
$failedTests = [];

function runTest(string $testName, callable $test): void {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    
    try {
        $result = $test();
        if ($result === true) {
            echo "✅ {$testName}\n";
            $passedTests++;
        } else {
            echo "❌ {$testName}: Test returned false\n";
            $failedTests[] = $testName;
        }
    } catch (Exception $e) {
        echo "❌ {$testName}: {$e->getMessage()}\n";
        $failedTests[] = $testName;
    }
}

function createTestLayer(string $type, array $properties = [], ?int $width = null, ?int $height = null): Layer {
    $layer = new Layer();
    $layer->setType($type);
    $layer->setName('test-layer');
    $layer->setProperties($properties);
    
    if ($width !== null) {
        $layer->setWidth($width);
    }
    if ($height !== null) {
        $layer->setHeight($height);
    }
    
    return $layer;
}

// =============================================================================
// BASIC GROUP RENDERING TESTS
// =============================================================================

echo "📦 Basic Group Rendering Tests...\n";

runTest("Basic: Empty group", function() use ($renderer, $builder) {
    $layer = createTestLayer('group', [], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<g') !== false &&
           strpos($xmlString, 'group-content-') !== false;
});

runTest("Basic: Group with dimensions", function() use ($renderer, $builder) {
    $layer = createTestLayer('group', [], 300, 150);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<g') !== false;
});

runTest("Basic: Group with name", function() use ($renderer, $builder) {
    $layer = createTestLayer('group', [], 200, 200);
    $layer->setName('my-custom-group');
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<g') !== false;
});

// =============================================================================
// BLEND MODE TESTS
// =============================================================================

echo "🎨 Blend Mode Tests...\n";

runTest("Blend: Normal (default)", function() use ($renderer, $builder) {
    $layer = createTestLayer('group', [
        'blendMode' => 'normal'
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Normal blend mode should not add style attribute
    return strpos($xmlString, '<g') !== false;
});

runTest("Blend: Multiply", function() use ($renderer, $builder) {
    $layer = createTestLayer('group', [
        'blendMode' => 'multiply'
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'mix-blend-mode: multiply') !== false;
});

runTest("Blend: Screen", function() use ($renderer, $builder) {
    $layer = createTestLayer('group', [
        'blendMode' => 'screen'
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'mix-blend-mode: screen') !== false;
});

runTest("Blend: Overlay", function() use ($renderer, $builder) {
    $layer = createTestLayer('group', [
        'blendMode' => 'overlay'
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'mix-blend-mode: overlay') !== false;
});

runTest("Blend: Invalid mode", function() use ($renderer, $builder) {
    $layer = createTestLayer('group', [
        'blendMode' => 'invalid-blend-mode'
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should fallback to normal (no style attribute)
    return strpos($xmlString, 'mix-blend-mode') === false;
});

// =============================================================================
// ISOLATION TESTS
// =============================================================================

echo "🔒 Isolation Tests...\n";

runTest("Isolation: Enabled", function() use ($renderer, $builder) {
    $layer = createTestLayer('group', [
        'isolation' => true
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'isolation: isolate') !== false;
});

runTest("Isolation: Disabled", function() use ($renderer, $builder) {
    $layer = createTestLayer('group', [
        'isolation' => false
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'isolation: isolate') === false;
});

runTest("Isolation: Combined with blend mode", function() use ($renderer, $builder) {
    $layer = createTestLayer('group', [
        'blendMode' => 'multiply',
        'isolation' => true
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'mix-blend-mode: multiply') !== false &&
           strpos($xmlString, 'isolation: isolate') !== false;
});

// =============================================================================
// CLIP PATH TESTS
// =============================================================================

echo "✂️ Clip Path Tests...\n";

runTest("ClipPath: Disabled boolean", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'clipPath' => false
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'clip-path') === false;
});

runTest("ClipPath: Enabled boolean (default rectangle)", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'clipPath' => true
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'clip-path="url(#group-clip-') !== false;
});

runTest("ClipPath: Rectangle", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'clipPath' => [
            'enabled' => true,
            'type' => 'rectangle',
            'x' => 10,
            'y' => 10,
            'width' => 180,
            'height' => 180,
            'cornerRadius' => 20
        ]
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'clip-path="url(#group-clip-') !== false &&
           strpos($xmlString, '<clipPath') !== false &&
           strpos($xmlString, '<rect') !== false &&
           strpos($xmlString, 'rx="20"') !== false;
});

runTest("ClipPath: Circle", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'clipPath' => [
            'enabled' => true,
            'type' => 'circle',
            'cx' => 100,
            'cy' => 100,
            'r' => 80
        ]
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'clip-path="url(#group-clip-') !== false &&
           strpos($xmlString, '<clipPath') !== false &&
           strpos($xmlString, '<circle') !== false &&
           strpos($xmlString, 'cx="100"') !== false &&
           strpos($xmlString, 'cy="100"') !== false &&
           strpos($xmlString, 'r="80"') !== false;
});

runTest("ClipPath: Ellipse", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'clipPath' => [
            'enabled' => true,
            'type' => 'ellipse',
            'cx' => 100,
            'cy' => 100,
            'rx' => 90,
            'ry' => 60
        ]
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'clip-path="url(#group-clip-') !== false &&
           strpos($xmlString, '<clipPath') !== false &&
           strpos($xmlString, '<ellipse') !== false &&
           strpos($xmlString, 'rx="90"') !== false &&
           strpos($xmlString, 'ry="60"') !== false;
});

runTest("ClipPath: Polygon", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'clipPath' => [
            'enabled' => true,
            'type' => 'polygon',
            'points' => [
                ['x' => 100, 'y' => 0],
                ['x' => 200, 'y' => 50],
                ['x' => 150, 'y' => 150],
                ['x' => 50, 'y' => 150]
            ]
        ]
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'clip-path="url(#group-clip-') !== false &&
           strpos($xmlString, '<clipPath') !== false &&
           strpos($xmlString, '<polygon') !== false &&
           strpos($xmlString, 'points="100,0 200,50 150,150 50,150"') !== false;
});

runTest("ClipPath: Path", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'clipPath' => [
            'enabled' => true,
            'type' => 'path',
            'd' => 'M 10,30 A 20,20 0,0,1 50,30 A 20,20 0,0,1 90,30'
        ]
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'clip-path="url(#group-clip-') !== false &&
           strpos($xmlString, '<clipPath') !== false &&
           strpos($xmlString, '<path') !== false &&
           strpos($xmlString, 'd="M 10,30 A 20,20 0,0,1 50,30') !== false;
});

runTest("ClipPath: Disabled in array", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'clipPath' => [
            'enabled' => false,
            'type' => 'rectangle'
        ]
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'clip-path') === false;
});

// =============================================================================
// MASK TESTS
// =============================================================================

echo "🎭 Mask Tests...\n";

runTest("Mask: Disabled boolean", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'mask' => false
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'mask') === false;
});

runTest("Mask: Enabled boolean (default gradient)", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'mask' => true
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'mask="url(#group-mask-') !== false;
});

runTest("Mask: Gradient mask", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'mask' => [
            'enabled' => true,
            'type' => 'gradient',
            'gradient' => [
                'type' => 'linear',
                'stops' => [
                    ['offset' => '0%', 'color' => '#ffffff', 'opacity' => 1],
                    ['offset' => '100%', 'color' => '#000000', 'opacity' => 0]
                ]
            ]
        ]
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'mask="url(#group-mask-') !== false &&
           strpos($xmlString, '<mask') !== false;
});

runTest("Mask: Shape mask - rectangle", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'mask' => [
            'enabled' => true,
            'type' => 'shape',
            'shapeType' => 'rectangle'
        ]
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'mask="url(#group-mask-') !== false &&
           strpos($xmlString, '<mask') !== false &&
           strpos($xmlString, '<rect') !== false &&
           strpos($xmlString, 'fill="white"') !== false;
});

runTest("Mask: Shape mask - circle", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'mask' => [
            'enabled' => true,
            'type' => 'shape',
            'shapeType' => 'circle'
        ]
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'mask="url(#group-mask-') !== false &&
           strpos($xmlString, '<mask') !== false &&
           strpos($xmlString, '<circle') !== false &&
           strpos($xmlString, 'fill="white"') !== false;
});

runTest("Mask: Image mask", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'mask' => [
            'enabled' => true,
            'type' => 'image',
            'src' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNDAiIGZpbGw9IndoaXRlIi8+PC9zdmc+'
        ]
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'mask="url(#group-mask-') !== false &&
           strpos($xmlString, '<mask') !== false &&
           strpos($xmlString, '<image') !== false;
});

runTest("Mask: Disabled in array", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'mask' => [
            'enabled' => false,
            'type' => 'gradient'
        ]
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'mask') === false;
});

// =============================================================================
// EDGE CASES AND VALIDATION TESTS
// =============================================================================

echo "🔍 Edge Cases and Validation Tests...\n";

runTest("Edge: Invalid clip path type", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'clipPath' => [
            'enabled' => true,
            'type' => 'invalid-shape'
        ]
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should not create clip path with invalid type
    return strpos($xmlString, 'clip-path') === false;
});

runTest("Edge: Invalid mask type", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'mask' => [
            'enabled' => true,
            'type' => 'invalid-mask-type'
        ]
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should not create mask with invalid type
    return strpos($xmlString, 'mask') === false;
});

runTest("Edge: Invalid path data", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'clipPath' => [
            'enabled' => true,
            'type' => 'path',
            'd' => 'INVALID_PATH_DATA_WITH_XSS<script>'
        ]
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should reject invalid path data
    return strpos($xmlString, '<script>') === false;
});

runTest("Edge: Empty polygon points", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(400, 400);
    
    $layer = createTestLayer('group', [
        'clipPath' => [
            'enabled' => true,
            'type' => 'polygon',
            'points' => []
        ]
    ], 200, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should create default triangle when no points provided
    return strpos($xmlString, 'clip-path="url(#group-clip-') !== false &&
           strpos($xmlString, '<polygon') !== false;
});

runTest("Edge: Negative dimensions", function() use ($renderer, $builder) {
    $layer = createTestLayer('group', [], -100, -50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<g') !== false;
});

// =============================================================================
// PERFORMANCE TESTS
// =============================================================================

echo "⚡ Performance Tests...\n";

runTest("Performance: Large group rendering", function() use ($renderer, $builder) {
    $startTime = microtime(true);
    
    for ($i = 0; $i < 50; $i++) {
        $layer = createTestLayer('group', [
            'blendMode' => 'multiply',
            'isolation' => true
        ], 200, 200);
        
        $svgElement = $renderer->render($layer, $builder);
    }
    
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    
    echo "   Rendered 50 group layers in " . number_format($executionTime, 4) . " seconds\n";
    return $executionTime < 1.0; // Should complete within 1 second
});

// =============================================================================
// COMPREHENSIVE GROUP GALLERY GENERATION
// =============================================================================

echo "📋 Generating comprehensive group gallery...\n";

try {
    $galleryBuilder = new SvgDocumentBuilder();
    $gallerySvg = $galleryBuilder->createDocument(1200, 800);
    $gallerySvg->setAttribute('style', 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);');
    
    // Create background
    $background = $galleryBuilder->createElement('rect');
    $background->setAttribute('width', '100%');
    $background->setAttribute('height', '100%');
    $background->setAttribute('fill', 'url(#bg-gradient)');
    $gallerySvg->appendChild($background);
    
    // Create background gradient
    $defs = $galleryBuilder->addDefinitions($gallerySvg);
    $bgGradient = $galleryBuilder->createLinearGradient('bg-gradient', [
        ['offset' => '0%', 'color' => '#667eea'],
        ['offset' => '100%', 'color' => '#764ba2']
    ], [], $gallerySvg->ownerDocument);
    $defs->appendChild($bgGradient);
    
    $groupConfigs = [
        // Row 1: Basic groups with blend modes
        ['x' => 50, 'y' => 100, 'props' => [], 'label' => 'Normal Group'],
        ['x' => 300, 'y' => 100, 'props' => ['blendMode' => 'multiply'], 'label' => 'Multiply Blend'],
        ['x' => 550, 'y' => 100, 'props' => ['blendMode' => 'screen'], 'label' => 'Screen Blend'],
        ['x' => 800, 'y' => 100, 'props' => ['blendMode' => 'overlay'], 'label' => 'Overlay Blend'],
        
        // Row 2: Isolation and complex blending
        ['x' => 50, 'y' => 250, 'props' => ['isolation' => true], 'label' => 'Isolated Group'],
        ['x' => 300, 'y' => 250, 'props' => ['blendMode' => 'difference', 'isolation' => true], 'label' => 'Isolated Difference'],
        ['x' => 550, 'y' => 250, 'props' => ['blendMode' => 'exclusion'], 'label' => 'Exclusion Blend'],
        ['x' => 800, 'y' => 250, 'props' => ['blendMode' => 'color-dodge'], 'label' => 'Color Dodge'],
        
        // Row 3: Clip path examples
        ['x' => 50, 'y' => 400, 'props' => [
            'clipPath' => [
                'enabled' => true,
                'type' => 'rectangle',
                'cornerRadius' => 20
            ]
        ], 'label' => 'Rounded Clip'],
        ['x' => 300, 'y' => 400, 'props' => [
            'clipPath' => [
                'enabled' => true,
                'type' => 'circle'
            ]
        ], 'label' => 'Circle Clip'],
        ['x' => 550, 'y' => 400, 'props' => [
            'clipPath' => [
                'enabled' => true,
                'type' => 'ellipse'
            ]
        ], 'label' => 'Ellipse Clip'],
        ['x' => 800, 'y' => 400, 'props' => [
            'mask' => [
                'enabled' => true,
                'type' => 'shape',
                'shapeType' => 'circle'
            ]
        ], 'label' => 'Circle Mask'],
        
        // Row 4: Complex combinations
        ['x' => 175, 'y' => 550, 'props' => [
            'blendMode' => 'multiply',
            'clipPath' => [
                'enabled' => true,
                'type' => 'rectangle',
                'cornerRadius' => 15
            ],
            'isolation' => true
        ], 'label' => 'Complex Group 1'],
        ['x' => 425, 'y' => 550, 'props' => [
            'blendMode' => 'overlay',
            'mask' => [
                'enabled' => true,
                'type' => 'gradient'
            ]
        ], 'label' => 'Complex Group 2'],
        ['x' => 675, 'y' => 550, 'props' => [
            'blendMode' => 'screen',
            'clipPath' => [
                'enabled' => true,
                'type' => 'circle'
            ],
            'isolation' => true
        ], 'label' => 'Complex Group 3']
    ];
    
    $layerCounter = 1;
    foreach ($groupConfigs as $config) {
        // Create group layer
        $layer = createTestLayer('group', $config['props'], 120, 80);
        $layer->setName('gallery-group-' . $layerCounter);
        $layer->setX($config['x']);
        $layer->setY($config['y']);
        
        $groupElement = $renderer->render($layer, $galleryBuilder);
        
        // Add a colored rectangle inside the group to show the effect
        $rect = $galleryBuilder->createElement('rect');
        $rect->setAttribute('x', '10');
        $rect->setAttribute('y', '10');
        $rect->setAttribute('width', '100');
        $rect->setAttribute('height', '60');
        $rect->setAttribute('fill', '#ff6b6b');
        $rect->setAttribute('opacity', '0.8');
        $groupElement->appendChild($rect);
        
        $gallerySvg->appendChild($groupElement);
        
        // Add label below the group
        $label = $galleryBuilder->createElement('text');
        $label->setAttribute('x', (string)($config['x'] + 60));
        $label->setAttribute('y', (string)($config['y'] + 110));
        $label->setAttribute('text-anchor', 'middle');
        $label->setAttribute('font-family', 'Arial, sans-serif');
        $label->setAttribute('font-size', '12');
        $label->setAttribute('fill', 'white');
        $label->appendChild($galleryBuilder->createText($config['label'], $label->ownerDocument));
        $gallerySvg->appendChild($label);
        
        $layerCounter++;
    }
    
    // Process all definitions
    $galleryBuilder->processDefinitions($gallerySvg);
    
    // Add title
    $title = $galleryBuilder->createElement('text');
    $title->setAttribute('x', '600');
    $title->setAttribute('y', '40');
    $title->setAttribute('text-anchor', 'middle');
    $title->setAttribute('font-family', 'Arial, sans-serif');
    $title->setAttribute('font-size', '28');
    $title->setAttribute('font-weight', 'bold');
    $title->setAttribute('fill', 'white');
    $title->setAttribute('stroke', 'rgba(0,0,0,0.3)');
    $title->setAttribute('stroke-width', '1');
    $title->appendChild($galleryBuilder->createText('Comprehensive Group Gallery', $title->ownerDocument));
    $gallerySvg->appendChild($title);
    
    // Save gallery
    $outputDir = __DIR__ . '/output';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    $xmlString = $gallerySvg->ownerDocument->saveXML($gallerySvg);
    $outputFile = $outputDir . '/comprehensive_group_gallery.svg';
    file_put_contents($outputFile, $xmlString);
    
    echo "✅ Gallery saved to: {$outputFile}\n";
    echo "📊 Generated " . count($groupConfigs) . " group examples\n";
    
} catch (Exception $e) {
    echo "❌ Gallery generation failed: {$e->getMessage()}\n";
}

// =============================================================================
// TEST RESULTS SUMMARY
// =============================================================================

echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 TEST RESULTS SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "Total tests: {$totalTests}\n";
echo "Passed: {$passedTests}\n";
echo "Failed: " . count($failedTests) . "\n";

if (!empty($failedTests)) {
    echo "\n❌ Failed tests:\n";
    foreach ($failedTests as $failedTest) {
        echo "  - {$failedTest}\n";
    }
}

$successRate = $totalTests > 0 ? ($passedTests / $totalTests) * 100 : 0;
echo "\n✨ Success rate: " . number_format($successRate, 1) . "%\n";

if ($successRate === 100.0) {
    echo "🎉 All tests passed! Group layer renderer is working perfectly.\n";
} elseif ($successRate >= 90.0) {
    echo "✅ Great! Most tests passed with minor issues.\n";
} elseif ($successRate >= 70.0) {
    echo "⚠️  Good progress, but some issues need attention.\n";
} else {
    echo "❌ Significant issues detected. Review implementation.\n";
}

echo "\n🏁 Group layer renderer test suite completed.\n";
