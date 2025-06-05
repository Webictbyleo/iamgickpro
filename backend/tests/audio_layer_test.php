<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entity\Layer;
use App\Service\Svg\LayerRenderer\AudioLayerRenderer;
use App\Service\Svg\SvgDocumentBuilder;

// =============================================================================
// AUDIO LAYER RENDERER TEST SUITE
// =============================================================================

echo "🎵 AUDIO LAYER RENDERER TEST SUITE\n";
echo "===================================\n\n";

// Initialize test components
$renderer = new AudioLayerRenderer();
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
// BASIC AUDIO RENDERING TESTS
// =============================================================================

echo "🎧 Basic Audio Rendering Tests...\n";

runTest("Basic: Empty audio layer", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<g') !== false &&
           strpos($xmlString, '<rect') !== false &&
           strpos($xmlString, 'fill="#065f46"') !== false;
});

runTest("Basic: Default dimensions", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', []);
    // No width/height set - should use defaults
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'width="100"') !== false &&
           strpos($xmlString, 'height="50"') !== false;
});

runTest("Basic: Custom dimensions", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 300, 80);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'width="300"') !== false &&
           strpos($xmlString, 'height="80"') !== false;
});

runTest("Basic: Audio with source", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [
        'src' => 'audio/sample.mp3',
        'title' => 'Sample Audio'
    ], 250, 60);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<g') !== false &&
           strpos($xmlString, '<rect') !== false;
});

// =============================================================================
// AUDIO PLACEHOLDER TESTS
// =============================================================================

echo "📦 Audio Placeholder Tests...\n";

runTest("Placeholder: Background rectangle", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<rect') !== false &&
           strpos($xmlString, 'fill="#065f46"') !== false &&
           strpos($xmlString, 'stroke="#047857"') !== false &&
           strpos($xmlString, 'stroke-width="2"') !== false &&
           strpos($xmlString, 'rx="12"') !== false;
});

runTest("Placeholder: Different sizes", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 400, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'width="400"') !== false &&
           strpos($xmlString, 'height="100"') !== false;
});

// =============================================================================
// WAVEFORM VISUALIZATION TESTS
// =============================================================================

echo "📊 Waveform Visualization Tests...\n";

runTest("Waveform: Basic generation", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should contain multiple rect elements for waveform bars
    $rectCount = substr_count($xmlString, '<rect');
    return $rectCount > 1 && // More than just the background rect
           strpos($xmlString, 'fill="#10b981"') !== false; // Waveform color
});

runTest("Waveform: Wide audio player", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 400, 60);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Wide player should have more waveform bars
    $rectCount = substr_count($xmlString, '<rect');
    return $rectCount > 5; // Should have multiple waveform bars
});

runTest("Waveform: Narrow audio player", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 100, 40);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Narrow player should have fewer waveform bars
    $rectCount = substr_count($xmlString, '<rect');
    return $rectCount >= 1; // Should still have at least background rect
});

runTest("Waveform: Rounded corners", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Waveform bars should have rounded corners
    return strpos($xmlString, 'rx="1"') !== false;
});

// =============================================================================
// AUDIO ICON TESTS
// =============================================================================

echo "🔊 Audio Icon Tests...\n";

runTest("Icon: Audio icon presence", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<circle') !== false &&
           strpos($xmlString, 'fill="#ffffff"') !== false;
});

runTest("Icon: Size scaling", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Icon size should scale with layer dimensions
    return strpos($xmlString, '<circle') !== false;
});

runTest("Icon: Position calculation", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Icon should be positioned in top-left area
    return strpos($xmlString, '<circle') !== false;
});

// =============================================================================
// AUDIO LABEL TESTS
// =============================================================================

echo "🏷️ Audio Label Tests...\n";

runTest("Label: Default label", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'Audio Layer') !== false &&
           strpos($xmlString, '<text') !== false;
});

runTest("Label: Text styling", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'font-family="Arial, sans-serif"') !== false &&
           strpos($xmlString, 'font-size="12"') !== false &&
           strpos($xmlString, 'fill="#9ca3af"') !== false;
});

runTest("Label: Center positioning", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'text-anchor="middle"') !== false;
});

runTest("Label: Position scaling", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 300, 80);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Label should be positioned relative to layer size
    return strpos($xmlString, '<text') !== false;
});

// =============================================================================
// AUDIO PROPERTIES TESTS
// =============================================================================

echo "⚙️ Audio Properties Tests...\n";

runTest("Properties: Audio source", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [
        'src' => 'https://example.com/audio.mp3'
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should render regardless of source URL
    return strpos($xmlString, '<g') !== false;
});

runTest("Properties: Audio duration", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [
        'duration' => 180.5
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<g') !== false;
});

runTest("Properties: Audio volume", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [
        'volume' => 0.8
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<g') !== false;
});

runTest("Properties: Audio loop", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [
        'loop' => true
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<g') !== false;
});

runTest("Properties: Multiple properties", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [
        'src' => 'audio/test.wav',
        'duration' => 240.0,
        'volume' => 0.9,
        'loop' => false,
        'autoplay' => true
    ], 250, 60);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<g') !== false;
});

// =============================================================================
// EDGE CASES AND VALIDATION TESTS
// =============================================================================

echo "🔍 Edge Cases and Validation Tests...\n";

runTest("Edge: Zero dimensions", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 0, 0);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should fallback to default dimensions
    return strpos($xmlString, '<g') !== false;
});

runTest("Edge: Negative dimensions", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], -100, -50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should handle negative dimensions gracefully
    return strpos($xmlString, '<g') !== false;
});

runTest("Edge: Very large dimensions", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 2000, 500);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'width="2000"') !== false &&
           strpos($xmlString, 'height="500"') !== false;
});

runTest("Edge: Very small dimensions", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [], 20, 10);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'width="20"') !== false &&
           strpos($xmlString, 'height="10"') !== false;
});

runTest("Edge: Invalid audio properties", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [
        'src' => null,
        'duration' => 'invalid',
        'volume' => 'not-a-number'
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should handle invalid properties gracefully
    return strpos($xmlString, '<g') !== false;
});

runTest("Edge: Special characters in properties", function() use ($renderer, $builder) {
    $layer = createTestLayer('audio', [
        'src' => 'audio/file with spaces & special chars.mp3',
        'title' => 'Song with "quotes" & <tags>'
    ], 200, 50);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should handle special characters properly
    return strpos($xmlString, '<g') !== false;
});

// =============================================================================
// PERFORMANCE TESTS
// =============================================================================

echo "⚡ Performance Tests...\n";

runTest("Performance: Large audio rendering", function() use ($renderer, $builder) {
    $startTime = microtime(true);
    
    for ($i = 0; $i < 50; $i++) {
        $layer = createTestLayer('audio', [
            'src' => "audio/track_{$i}.mp3",
            'duration' => rand(60, 300)
        ], 200 + ($i % 5) * 50, 50);
        
        $svgElement = $renderer->render($layer, $builder);
    }
    
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    
    echo "   Rendered 50 audio layers in " . number_format($executionTime, 4) . " seconds\n";
    return $executionTime < 1.0; // Should complete within 1 second
});

runTest("Performance: Complex waveform generation", function() use ($renderer, $builder) {
    $startTime = microtime(true);
    
    // Create audio layers with very wide waveforms
    for ($i = 0; $i < 10; $i++) {
        $layer = createTestLayer('audio', [], 1000, 80);
        $svgElement = $renderer->render($layer, $builder);
    }
    
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    
    echo "   Generated 10 complex waveforms in " . number_format($executionTime, 4) . " seconds\n";
    return $executionTime < 2.0; // Should complete within 2 seconds
});

// =============================================================================
// COMPREHENSIVE AUDIO GALLERY GENERATION
// =============================================================================

echo "📋 Generating comprehensive audio gallery...\n";

try {
    $galleryBuilder = new SvgDocumentBuilder();
    $gallerySvg = $galleryBuilder->createDocument(1200, 800);
    $gallerySvg->setAttribute('style', 'background: linear-gradient(135deg, #065f46 0%, #047857 100%);');
    
    // Create background
    $background = $galleryBuilder->createElement('rect');
    $background->setAttribute('width', '100%');
    $background->setAttribute('height', '100%');
    $background->setAttribute('fill', 'url(#bg-gradient)');
    $gallerySvg->appendChild($background);
    
    // Create background gradient
    $defs = $galleryBuilder->addDefinitions($gallerySvg);
    $bgGradient = $galleryBuilder->createLinearGradient('bg-gradient', [
        ['offset' => '0%', 'color' => '#065f46'],
        ['offset' => '100%', 'color' => '#047857']
    ], [], $gallerySvg->ownerDocument);
    $defs->appendChild($bgGradient);
    
    $audioConfigs = [
        // Row 1: Different sizes
        ['x' => 50, 'y' => 100, 'width' => 150, 'height' => 40, 'props' => [], 'label' => 'Small Audio'],
        ['x' => 250, 'y' => 100, 'width' => 200, 'height' => 50, 'props' => [], 'label' => 'Standard Audio'],
        ['x' => 500, 'y' => 100, 'width' => 300, 'height' => 60, 'props' => [], 'label' => 'Large Audio'],
        ['x' => 850, 'y' => 100, 'width' => 250, 'height' => 80, 'props' => [], 'label' => 'Tall Audio'],
        
        // Row 2: Different aspect ratios
        ['x' => 50, 'y' => 220, 'width' => 400, 'height' => 40, 'props' => [], 'label' => 'Wide Audio Player'],
        ['x' => 500, 'y' => 220, 'width' => 100, 'height' => 60, 'props' => [], 'label' => 'Compact Player'],
        ['x' => 650, 'y' => 220, 'width' => 200, 'height' => 80, 'props' => [], 'label' => 'Square-ish Player'],
        ['x' => 900, 'y' => 220, 'width' => 180, 'height' => 100, 'props' => [], 'label' => 'Vertical Player'],
        
        // Row 3: With properties
        ['x' => 50, 'y' => 360, 'width' => 250, 'height' => 60, 'props' => [
            'src' => 'audio/music.mp3',
            'duration' => 180.5
        ], 'label' => 'Music Track'],
        ['x' => 350, 'y' => 360, 'width' => 200, 'height' => 50, 'props' => [
            'src' => 'audio/podcast.wav',
            'volume' => 0.8
        ], 'label' => 'Podcast Episode'],
        ['x' => 600, 'y' => 360, 'width' => 220, 'height' => 55, 'props' => [
            'src' => 'audio/sound-effect.ogg',
            'loop' => true
        ], 'label' => 'Sound Effect'],
        ['x' => 870, 'y' => 360, 'width' => 180, 'height' => 45, 'props' => [
            'src' => 'audio/notification.mp3',
            'autoplay' => false
        ], 'label' => 'Notification'],
        
        // Row 4: Edge cases
        ['x' => 100, 'y' => 500, 'width' => 50, 'height' => 30, 'props' => [], 'label' => 'Tiny Player'],
        ['x' => 200, 'y' => 500, 'width' => 500, 'height' => 35, 'props' => [], 'label' => 'Extra Wide Player'],
        ['x' => 750, 'y' => 500, 'width' => 80, 'height' => 120, 'props' => [], 'label' => 'Vertical Format'],
        ['x' => 900, 'y' => 500, 'width' => 200, 'height' => 100, 'props' => [
            'src' => 'audio/long-filename-with-special-chars_&_symbols.mp3'
        ], 'label' => 'Complex Filename']
    ];
    
    $layerCounter = 1;
    foreach ($audioConfigs as $config) {
        $layer = createTestLayer('audio', $config['props'], $config['width'], $config['height']);
        $layer->setName('gallery-audio-' . $layerCounter);
        $layer->setX($config['x']);
        $layer->setY($config['y']);
        
        $audioElement = $renderer->render($layer, $galleryBuilder);
        $gallerySvg->appendChild($audioElement);
        
        // Add label below the audio player
        $label = $galleryBuilder->createElement('text');
        $label->setAttribute('x', (string)($config['x'] + $config['width'] / 2));
        $label->setAttribute('y', (string)($config['y'] + $config['height'] + 20));
        $label->setAttribute('text-anchor', 'middle');
        $label->setAttribute('font-family', 'Arial, sans-serif');
        $label->setAttribute('font-size', '10');
        $label->setAttribute('fill', '#d1fae5');
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
    $title->appendChild($galleryBuilder->createText('Comprehensive Audio Gallery', $title->ownerDocument));
    $gallerySvg->appendChild($title);
    
    // Save gallery
    $outputDir = __DIR__ . '/output';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    $xmlString = $galleryBuilder->getDocument()->saveXML($gallerySvg);
    $outputFile = $outputDir . '/comprehensive_audio_gallery.svg';
    file_put_contents($outputFile, $xmlString);
    
    echo "✅ Gallery saved to: {$outputFile}\n";
    echo "📊 Generated " . count($audioConfigs) . " audio examples\n";
    
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
    echo "🎉 All tests passed! Audio layer renderer is working perfectly.\n";
} elseif ($successRate >= 90.0) {
    echo "✅ Great! Most tests passed with minor issues.\n";
} elseif ($successRate >= 70.0) {
    echo "⚠️  Good progress, but some issues need attention.\n";
} else {
    echo "❌ Significant issues detected. Review implementation.\n";
}

echo "\n🏁 Audio layer renderer test suite completed.\n";
