<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entity\Layer;
use App\Service\Svg\LayerRenderer\TextLayerRenderer;
use App\Service\Svg\SvgDocumentBuilder;
use App\Service\Svg\SvgTransformBuilder;

// =============================================================================
// TEXT LAYER RENDERER TEST SUITE
// =============================================================================

echo "🎨 TEXT LAYER RENDERER TEST SUITE\n";
echo "==================================\n\n";

// Initialize test components
$transformBuilder = new SvgTransformBuilder();
$renderer = new TextLayerRenderer($transformBuilder);
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
// BASIC TEXT RENDERING TESTS
// =============================================================================

echo "📝 Basic Text Rendering Tests...\n";

runTest("Basic: Simple text", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'Hello World',
        'fontSize' => 16,
        'color' => '#000000'
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'Hello World') !== false &&
           strpos($xmlString, 'font-size="16"') !== false &&
           strpos($xmlString, 'fill="#000000"') !== false;
});

runTest("Basic: Multi-line text", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => "Line 1\nLine 2\nLine 3",
        'fontSize' => 14,
        'lineHeight' => 1.4
    ], 200, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<tspan') !== false &&
           strpos($xmlString, 'Line 1') !== false &&
           strpos($xmlString, 'Line 2') !== false &&
           strpos($xmlString, 'Line 3') !== false;
});

runTest("Basic: Empty text", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => '',
        'fontSize' => 16
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<text') !== false;
});

// =============================================================================
// FONT PROPERTY TESTS
// =============================================================================

echo "🔤 Font Property Tests...\n";

runTest("Font: Family", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'Custom Font',
        'fontFamily' => 'Helvetica, Arial, sans-serif',
        'fontSize' => 18
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'font-family="Helvetica, Arial, sans-serif"') !== false;
});

runTest("Font: Weight - Bold", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'Bold Text',
        'fontWeight' => 'bold',
        'fontSize' => 16
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'font-weight="bold"') !== false;
});

runTest("Font: Weight - Numeric", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'Semi-bold Text',
        'fontWeight' => '600',
        'fontSize' => 16
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'font-weight="600"') !== false;
});

runTest("Font: Style - Italic", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'Italic Text',
        'fontStyle' => 'italic',
        'fontSize' => 16
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'font-style="italic"') !== false;
});

runTest("Font: Size - Large", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'Large Text',
        'fontSize' => 48
    ], 300, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'font-size="48"') !== false;
});

runTest("Font: Size - Small", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'Small Text',
        'fontSize' => 10
    ], 100, 30);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'font-size="10"') !== false;
});

// =============================================================================
// TEXT ALIGNMENT TESTS
// =============================================================================

echo "📐 Text Alignment Tests...\n";

runTest("Align: Left", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'Left Aligned',
        'textAlign' => 'left',
        'fontSize' => 16
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'text-anchor="start"') !== false;
});

runTest("Align: Center", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'Center Aligned',
        'textAlign' => 'center',
        'fontSize' => 16
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'text-anchor="middle"') !== false;
});

runTest("Align: Right", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'Right Aligned',
        'textAlign' => 'right',
        'fontSize' => 16
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'text-anchor="end"') !== false;
});

// =============================================================================
// COLOR AND DECORATION TESTS
// =============================================================================

echo "🎨 Color and Decoration Tests...\n";

runTest("Color: Hex", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'Colored Text',
        'color' => '#ff6b6b',
        'fontSize' => 16
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'fill="#ff6b6b"') !== false;
});

runTest("Color: RGB", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'RGB Text',
        'color' => 'rgb(255, 107, 107)',
        'fontSize' => 16
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'fill=') !== false;
});

runTest("Decoration: Underline", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'Underlined Text',
        'textDecoration' => 'underline',
        'fontSize' => 16
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'text-decoration="underline"') !== false;
});

runTest("Decoration: Line-through", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'Strikethrough Text',
        'textDecoration' => 'line-through',
        'fontSize' => 16
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'text-decoration="line-through"') !== false;
});

// =============================================================================
// SPACING AND LAYOUT TESTS
// =============================================================================

echo "📏 Spacing and Layout Tests...\n";

runTest("Spacing: Letter spacing", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'Spaced Text',
        'letterSpacing' => 2.5,
        'fontSize' => 16
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'letter-spacing="2.5"') !== false;
});

runTest("Spacing: Line height", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => "Line 1\nLine 2",
        'lineHeight' => 2.0,
        'fontSize' => 16
    ], 200, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<tspan') !== false;
});

// =============================================================================
// EDGE CASES AND VALIDATION TESTS
// =============================================================================

echo "🔍 Edge Cases and Validation Tests...\n";

runTest("Edge: Invalid font size", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'Test Text',
        'fontSize' => -10, // Invalid negative size
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should fallback to minimum valid font size
    return strpos($xmlString, 'font-size=') !== false;
});

runTest("Edge: Invalid color", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'Test Text',
        'color' => 'invalid-color',
        'fontSize' => 16
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should fallback to valid color
    return strpos($xmlString, 'fill=') !== false;
});

runTest("Edge: Special characters", function() use ($renderer, $builder) {
    $layer = createTestLayer('text', [
        'text' => 'Special: &<>"\'',
        'fontSize' => 16
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should properly escape XML entities
    return strpos($xmlString, '<text') !== false;
});

runTest("Edge: Very long text", function() use ($renderer, $builder) {
    $longText = str_repeat('Very long text that should be handled properly. ', 20);
    $layer = createTestLayer('text', [
        'text' => $longText,
        'fontSize' => 14
    ], 400, 200);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<text') !== false;
});

// =============================================================================
// PERFORMANCE TESTS
// =============================================================================

echo "⚡ Performance Tests...\n";

runTest("Performance: Large text rendering", function() use ($renderer, $builder) {
    $startTime = microtime(true);
    
    for ($i = 0; $i < 50; $i++) {
        $layer = createTestLayer('text', [
            'text' => "Performance test text line {$i}",
            'fontSize' => 16,
            'color' => '#333333'
        ], 300, 50);
        
        $svgElement = $renderer->render($layer, $builder);
    }
    
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    
    echo "   Rendered 50 text layers in " . number_format($executionTime, 4) . " seconds\n";
    return $executionTime < 1.0; // Should complete within 1 second
});

// =============================================================================
// COMPREHENSIVE TEXT GALLERY GENERATION
// =============================================================================

echo "📋 Generating comprehensive text gallery...\n";

try {
    $galleryBuilder = new SvgDocumentBuilder();
    $gallerySvg = $galleryBuilder->createDocument(1200, 1000);
    $gallerySvg->setAttribute('style', 'background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);');
    
    // Create background
    $background = $galleryBuilder->createElement('rect');
    $background->setAttribute('width', '100%');
    $background->setAttribute('height', '100%');
    $background->setAttribute('fill', 'url(#bg-gradient)');
    $gallerySvg->appendChild($background);
    
    // Create background gradient
    $defs = $galleryBuilder->addDefinitions($gallerySvg);
    $bgGradient = $galleryBuilder->createLinearGradient('bg-gradient', [
        ['offset' => '0%', 'color' => '#f093fb'],
        ['offset' => '100%', 'color' => '#f5576c']
    ], [], $gallerySvg->ownerDocument);
    $defs->appendChild($bgGradient);
    
    $textConfigs = [
        // Row 1: Basic text styles
        ['text' => 'Regular Text', 'x' => 50, 'y' => 80, 'props' => ['fontSize' => 18, 'color' => '#ffffff']],
        ['text' => 'Bold Text', 'x' => 300, 'y' => 80, 'props' => ['fontSize' => 18, 'fontWeight' => 'bold', 'color' => '#ffffff']],
        ['text' => 'Italic Text', 'x' => 550, 'y' => 80, 'props' => ['fontSize' => 18, 'fontStyle' => 'italic', 'color' => '#ffffff']],
        ['text' => 'Underlined', 'x' => 800, 'y' => 80, 'props' => ['fontSize' => 18, 'textDecoration' => 'underline', 'color' => '#ffffff']],
        
        // Row 2: Alignment
        ['text' => 'Left Aligned', 'x' => 50, 'y' => 180, 'props' => ['fontSize' => 16, 'textAlign' => 'left', 'color' => '#ffffff']],
        ['text' => 'Center Aligned', 'x' => 300, 'y' => 180, 'props' => ['fontSize' => 16, 'textAlign' => 'center', 'color' => '#ffffff']],
        ['text' => 'Right Aligned', 'x' => 550, 'y' => 180, 'props' => ['fontSize' => 16, 'textAlign' => 'right', 'color' => '#ffffff']],
        
        // Row 3: Font sizes
        ['text' => 'Small', 'x' => 50, 'y' => 280, 'props' => ['fontSize' => 12, 'color' => '#ffffff']],
        ['text' => 'Medium', 'x' => 200, 'y' => 280, 'props' => ['fontSize' => 18, 'color' => '#ffffff']],
        ['text' => 'Large', 'x' => 350, 'y' => 280, 'props' => ['fontSize' => 24, 'color' => '#ffffff']],
        ['text' => 'Extra Large', 'x' => 550, 'y' => 280, 'props' => ['fontSize' => 32, 'color' => '#ffffff']],
        
        // Row 4: Colors
        ['text' => 'Red Text', 'x' => 50, 'y' => 380, 'props' => ['fontSize' => 16, 'color' => '#ff6b6b']],
        ['text' => 'Blue Text', 'x' => 200, 'y' => 380, 'props' => ['fontSize' => 16, 'color' => '#4dabf7']],
        ['text' => 'Green Text', 'x' => 350, 'y' => 380, 'props' => ['fontSize' => 16, 'color' => '#51cf66']],
        ['text' => 'Yellow Text', 'x' => 500, 'y' => 380, 'props' => ['fontSize' => 16, 'color' => '#ffd43b']],
        
        // Row 5: Multi-line text
        ['text' => "Multi-line\nText Example\nWith Line Breaks", 'x' => 50, 'y' => 480, 'props' => ['fontSize' => 14, 'lineHeight' => 1.4, 'color' => '#ffffff']],
        ['text' => "Spaced Out\nText Example", 'x' => 300, 'y' => 480, 'props' => ['fontSize' => 16, 'letterSpacing' => 3, 'lineHeight' => 1.6, 'color' => '#ffffff']],
        
        // Row 6: Font families
        ['text' => 'Sans Serif', 'x' => 50, 'y' => 680, 'props' => ['fontSize' => 16, 'fontFamily' => 'Arial, sans-serif', 'color' => '#ffffff']],
        ['text' => 'Serif Text', 'x' => 200, 'y' => 680, 'props' => ['fontSize' => 16, 'fontFamily' => 'Times, serif', 'color' => '#ffffff']],
        ['text' => 'Monospace', 'x' => 350, 'y' => 680, 'props' => ['fontSize' => 16, 'fontFamily' => 'Courier, monospace', 'color' => '#ffffff']],
    ];
    
    $layerCounter = 1;
    foreach ($textConfigs as $config) {
        $layer = createTestLayer('text', array_merge([
            'text' => $config['text']
        ], $config['props']), 200, 80);
        
        // Set layer position
        $layer->setName('gallery-text-' . $layerCounter);
        $layer->setX($config['x']);
        $layer->setY($config['y']);
        
        $textElement = $renderer->render($layer, $galleryBuilder);
        $gallerySvg->appendChild($textElement);
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
    $title->appendChild($galleryBuilder->createText('Comprehensive Text Gallery', $title->ownerDocument));
    $gallerySvg->appendChild($title);
    
    // Save gallery
    $outputDir = __DIR__ . '/output';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    $xmlString = $gallerySvg->ownerDocument->saveXML($gallerySvg);
    $outputFile = $outputDir . '/comprehensive_text_gallery.svg';
    file_put_contents($outputFile, $xmlString);
    
    echo "✅ Gallery saved to: {$outputFile}\n";
    echo "📊 Generated " . count($textConfigs) . " text examples\n";
    
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
    echo "🎉 All tests passed! Text layer renderer is working perfectly.\n";
} elseif ($successRate >= 90.0) {
    echo "✅ Great! Most tests passed with minor issues.\n";
} elseif ($successRate >= 70.0) {
    echo "⚠️  Good progress, but some issues need attention.\n";
} else {
    echo "❌ Significant issues detected. Review implementation.\n";
}

echo "\n🏁 Text layer renderer test suite completed.\n";
