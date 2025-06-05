<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entity\Layer;
use App\Service\Svg\LayerRenderer\TextLayerRenderer;
use App\Service\Svg\LayerRenderer\GroupLayerRenderer;
use App\Service\Svg\LayerRenderer\AudioLayerRenderer;
use App\Service\Svg\LayerRenderer\VideoLayerRenderer;
use App\Service\Svg\SvgDocumentBuilder;
use App\Service\Svg\SvgTransformBuilder;

// =============================================================================
// SIMPLIFIED LAYER RENDERER TEST RUNNER
// =============================================================================

echo "🧪 SIMPLIFIED LAYER RENDERER TEST RUNNER\n";
echo "==========================================\n\n";

// Initialize transform builder for all renderers
$transformBuilder = new SvgTransformBuilder();

function createTestLayer(string $type, array $properties = [], ?int $width = null, ?int $height = null): Layer {
    $layer = new Layer();
    $layer->setType($type);
    $layer->setName('test-layer-' . rand(1000, 9999));
    $layer->setProperties($properties);
    
    if ($width !== null) {
        $layer->setWidth($width);
    }
    if ($height !== null) {
        $layer->setHeight($height);
    }
    
    return $layer;
}

function testRenderer(string $rendererName, $renderer, array $testCases): array {
    echo "🔧 Testing {$rendererName}...\n";
    
    $builder = new SvgDocumentBuilder();
    $results = [];
    
    foreach ($testCases as $testName => $testData) {
        try {
            $layer = createTestLayer(
                $testData['type'],
                $testData['properties'] ?? [],
                $testData['width'] ?? null,
                $testData['height'] ?? null
            );
            
            $startTime = microtime(true);
            $svgElement = $renderer->render($layer, $builder);
            $endTime = microtime(true);
            
            $xmlString = $builder->saveDocument($svgElement);
            $renderTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            $success = !empty($xmlString) && strlen($xmlString) > 10;
            
            if ($success) {
                echo "  ✅ {$testName} (" . number_format($renderTime, 2) . "ms)\n";
            } else {
                echo "  ❌ {$testName}: Empty or invalid output\n";
            }
            
            $results[] = [
                'name' => $testName,
                'success' => $success,
                'time' => $renderTime,
                'error' => null
            ];
            
        } catch (Exception $e) {
            echo "  ❌ {$testName}: {$e->getMessage()}\n";
            $results[] = [
                'name' => $testName,
                'success' => false,
                'time' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    return $results;
}

// =============================================================================
// TEXT LAYER TESTS
// =============================================================================

$textTestCases = [
    'Basic Text' => [
        'type' => 'text',
        'properties' => ['text' => 'Hello World', 'fontSize' => 16],
        'width' => 200,
        'height' => 50
    ],
    'Multi-line Text' => [
        'type' => 'text',
        'properties' => ['text' => "Line 1\nLine 2\nLine 3", 'fontSize' => 14],
        'width' => 200,
        'height' => 100
    ],
    'Bold Text' => [
        'type' => 'text',
        'properties' => ['text' => 'Bold Text', 'fontSize' => 16, 'fontWeight' => 'bold'],
        'width' => 200,
        'height' => 50
    ],
    'Colored Text' => [
        'type' => 'text',
        'properties' => ['text' => 'Red Text', 'fontSize' => 16, 'color' => '#ff0000'],
        'width' => 200,
        'height' => 50
    ],
    'Aligned Text' => [
        'type' => 'text',
        'properties' => ['text' => 'Center Text', 'fontSize' => 16, 'textAlign' => 'center'],
        'width' => 200,
        'height' => 50
    ]
];

$textRenderer = new TextLayerRenderer($transformBuilder);
$textResults = testRenderer('Text Layer Renderer', $textRenderer, $textTestCases);

// =============================================================================
// GROUP LAYER TESTS
// =============================================================================

$groupTestCases = [
    'Basic Group' => [
        'type' => 'group',
        'properties' => [],
        'width' => 200,
        'height' => 100
    ],
    'Group with Blend Mode' => [
        'type' => 'group',
        'properties' => ['blendMode' => 'multiply'],
        'width' => 200,
        'height' => 100
    ],
    'Group with Isolation' => [
        'type' => 'group',
        'properties' => ['isolation' => true],
        'width' => 200,
        'height' => 100
    ],
    'Group with Clip Path' => [
        'type' => 'group',
        'properties' => ['clipPath' => ['enabled' => true, 'type' => 'rectangle']],
        'width' => 200,
        'height' => 100
    ],
    'Group with Mask' => [
        'type' => 'group',
        'properties' => ['mask' => ['enabled' => true, 'type' => 'gradient']],
        'width' => 200,
        'height' => 100
    ]
];

$groupRenderer = new GroupLayerRenderer($transformBuilder);
$groupResults = testRenderer('Group Layer Renderer', $groupRenderer, $groupTestCases);

// =============================================================================
// AUDIO LAYER TESTS
// =============================================================================

$audioTestCases = [
    'Basic Audio' => [
        'type' => 'audio',
        'properties' => [],
        'width' => 300,
        'height' => 80
    ],
    'Audio with Source' => [
        'type' => 'audio',
        'properties' => ['src' => 'audio.mp3', 'duration' => 120],
        'width' => 300,
        'height' => 80
    ],
    'Compact Audio' => [
        'type' => 'audio',
        'properties' => [],
        'width' => 200,
        'height' => 50
    ],
    'Large Audio' => [
        'type' => 'audio',
        'properties' => [],
        'width' => 500,
        'height' => 120
    ]
];

$audioRenderer = new AudioLayerRenderer($transformBuilder);
$audioResults = testRenderer('Audio Layer Renderer', $audioRenderer, $audioTestCases);

// =============================================================================
// VIDEO LAYER TESTS
// =============================================================================

$videoTestCases = [
    'Basic Video' => [
        'type' => 'video',
        'properties' => [],
        'width' => 320,
        'height' => 240
    ],
    'Video with Source' => [
        'type' => 'video',
        'properties' => ['src' => 'video.mp4', 'duration' => 180],
        'width' => 320,
        'height' => 240
    ],
    'Small Video' => [
        'type' => 'video',
        'properties' => [],
        'width' => 160,
        'height' => 120
    ],
    'Large Video' => [
        'type' => 'video',
        'properties' => [],
        'width' => 640,
        'height' => 480
    ],
    'Square Video' => [
        'type' => 'video',
        'properties' => [],
        'width' => 300,
        'height' => 300
    ]
];

$videoRenderer = new VideoLayerRenderer();
$videoResults = testRenderer('Video Layer Renderer', $videoRenderer, $videoTestCases);

// =============================================================================
// PERFORMANCE TEST
// =============================================================================

echo "\n⚡ Performance Test...\n";

$startTime = microtime(true);
$performanceResults = [];

for ($i = 0; $i < 20; $i++) {
    // Test different layer types
    $layers = [
        createTestLayer('text', ['text' => "Performance test $i", 'fontSize' => 16], 200, 50),
        createTestLayer('group', [], 150, 100),
        createTestLayer('audio', [], 300, 60),
        createTestLayer('video', [], 200, 150)
    ];
    
    $renderers = [$textRenderer, $groupRenderer, $audioRenderer, $videoRenderer];
    
    foreach ($layers as $index => $layer) {
        try {
            $builder = new SvgDocumentBuilder();
            $renderer = $renderers[$index];
            $svgElement = $renderer->render($layer, $builder);
            $xmlString = $builder->saveDocument($svgElement);
            
            if (!empty($xmlString)) {
                $performanceResults[] = true;
            }
        } catch (Exception $e) {
            $performanceResults[] = false;
        }
    }
}

$endTime = microtime(true);
$totalTime = $endTime - $startTime;
$successCount = count(array_filter($performanceResults));
$totalOperations = count($performanceResults);

echo "  Completed {$totalOperations} render operations in " . number_format($totalTime, 3) . "s\n";
echo "  Success rate: {$successCount}/{$totalOperations} (" . round(($successCount / $totalOperations) * 100, 1) . "%)\n";
echo "  Average time per operation: " . round(($totalTime / $totalOperations) * 1000, 2) . "ms\n";

// =============================================================================
// GENERATE SIMPLE GALLERY
// =============================================================================

echo "\n📋 Generating combined gallery...\n";

try {
    $galleryBuilder = new SvgDocumentBuilder();
    $gallerySvg = $galleryBuilder->createDocument(1200, 800);
    
    // Background
    $background = $galleryBuilder->createElement('rect');
    $background->setAttribute('width', '100%');
    $background->setAttribute('height', '100%');
    $background->setAttribute('fill', '#f8fafc');
    $gallerySvg->appendChild($background);
    
    // Title
    $title = $galleryBuilder->createElement('text');
    $title->setAttribute('x', '600');
    $title->setAttribute('y', '30');
    $title->setAttribute('text-anchor', 'middle');
    $title->setAttribute('font-family', 'Arial, sans-serif');
    $title->setAttribute('font-size', '24');
    $title->setAttribute('font-weight', 'bold');
    $title->setAttribute('fill', '#1f2937');
    $title->appendChild($galleryBuilder->createText('Layer Renderer Test Gallery', $title->ownerDocument));
    $gallerySvg->appendChild($title);
    
    // Create sample layers
    $sampleLayers = [
        // Text examples
        ['type' => 'text', 'props' => ['text' => 'Sample Text', 'fontSize' => 18], 'x' => 50, 'y' => 80, 'w' => 200, 'h' => 40],
        ['type' => 'text', 'props' => ['text' => 'Bold Text', 'fontSize' => 16, 'fontWeight' => 'bold'], 'x' => 300, 'y' => 80, 'w' => 200, 'h' => 40],
        
        // Group examples
        ['type' => 'group', 'props' => [], 'x' => 550, 'y' => 80, 'w' => 150, 'h' => 100],
        ['type' => 'group', 'props' => ['blendMode' => 'multiply'], 'x' => 750, 'y' => 80, 'w' => 150, 'h' => 100],
        
        // Audio examples
        ['type' => 'audio', 'props' => [], 'x' => 50, 'y' => 250, 'w' => 250, 'h' => 60],
        ['type' => 'audio', 'props' => [], 'x' => 350, 'y' => 250, 'w' => 300, 'h' => 80],
        
        // Video examples
        ['type' => 'video', 'props' => [], 'x' => 50, 'y' => 400, 'w' => 200, 'h' => 150],
        ['type' => 'video', 'props' => [], 'x' => 300, 'y' => 400, 'w' => 250, 'h' => 180],
        ['type' => 'video', 'props' => [], 'x' => 600, 'y' => 400, 'w' => 300, 'h' => 200]
    ];
    
    $rendererMap = [
        'text' => $textRenderer,
        'group' => $groupRenderer,
        'audio' => $audioRenderer,
        'video' => $videoRenderer
    ];
    
    foreach ($sampleLayers as $layerData) {
        $layer = createTestLayer($layerData['type'], $layerData['props'], $layerData['w'], $layerData['h']);
        $renderer = $rendererMap[$layerData['type']];
        
        try {
            $element = $renderer->render($layer, $galleryBuilder);
            $element->setAttribute('transform', "translate({$layerData['x']}, {$layerData['y']})");
            $gallerySvg->appendChild($element);
            
            // Add label
            $label = $galleryBuilder->createElement('text');
            $label->setAttribute('x', (string)($layerData['x'] + $layerData['w'] / 2));
            $label->setAttribute('y', (string)($layerData['y'] + $layerData['h'] + 20));
            $label->setAttribute('text-anchor', 'middle');
            $label->setAttribute('font-family', 'Arial, sans-serif');
            $label->setAttribute('font-size', '12');
            $label->setAttribute('fill', '#6b7280');
            $label->appendChild($galleryBuilder->createText(ucfirst($layerData['type']) . ' Layer', $label->ownerDocument));
            $gallerySvg->appendChild($label);
            
        } catch (Exception $e) {
            echo "  ⚠️  Failed to render {$layerData['type']} layer: {$e->getMessage()}\n";
        }
    }
    
    // Process definitions
    $galleryBuilder->processDefinitions($gallerySvg);
    
    // Save gallery
    $outputDir = __DIR__ . '/output';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    $xmlString = $galleryBuilder->getDocument()->saveXML($gallerySvg);
    $outputFile = $outputDir . '/simple_layer_gallery.svg';
    file_put_contents($outputFile, $xmlString);
    
    echo "  ✅ Gallery saved to: {$outputFile}\n";
    
} catch (Exception $e) {
    echo "  ❌ Gallery generation failed: {$e->getMessage()}\n";
}

// =============================================================================
// SUMMARY
// =============================================================================

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 LAYER RENDERER TEST SUMMARY\n";
echo str_repeat("=", 60) . "\n";

$allResults = array_merge($textResults, $groupResults, $audioResults, $videoResults);
$totalTests = count($allResults);
$passedCount = 0;
$failedCount = 0;

foreach ($allResults as $result) {
    if ($result['success']) {
        $passedCount++;
    } else {
        $failedCount++;
    }
}

echo "Total tests: {$totalTests}\n";
echo "Passed: {$passedCount}\n";
echo "Failed: {$failedCount}\n";
echo "Success rate: " . round(($passedCount / $totalTests) * 100, 1) . "%\n";

if ($failedCount > 0) {
    echo "\n❌ Failed tests:\n";
    foreach ($allResults as $result) {
        if (!$result['success']) {
            echo "  - {$result['name']}: {$result['error']}\n";
        }
    }
}

echo "\n📈 Performance Summary:\n";
echo "  Total render operations: {$totalOperations}\n";
echo "  Successful operations: {$successCount}\n";
echo "  Total time: " . number_format($totalTime, 3) . "s\n";
echo "  Average per operation: " . round(($totalTime / $totalOperations) * 1000, 2) . "ms\n";

if ($passedCount === $totalTests) {
    echo "\n🎉 All layer renderers are working perfectly!\n";
} elseif ($passedCount >= $totalTests * 0.9) {
    echo "\n✅ Excellent! Most layer renderers are working well.\n";
} elseif ($passedCount >= $totalTests * 0.7) {
    echo "\n⚠️  Good progress, but some issues need attention.\n";
} else {
    echo "\n❌ Significant issues detected. Review implementations.\n";
}

echo "\n🏁 Layer renderer test suite completed.\n";
