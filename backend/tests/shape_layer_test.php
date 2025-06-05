<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Entity\Layer;
use App\Service\Svg\LayerRenderer\ShapeLayerRenderer;
use App\Service\Svg\SvgDocumentBuilder;
use App\Service\Svg\SvgTransformBuilder;

/**
 * COMPREHENSIVE SHAPE LAYER PROCESSING TEST SUITE
 * 
 * Tests all shape types, fill types, stroke properties, and effects
 * to ensure proper SVG generation and rendering quality.
 */

echo "🔶 COMPREHENSIVE SHAPE LAYER PROCESSING TEST SUITE\n";
echo str_repeat("=", 70) . "\n\n";

// Initialize components
$transformBuilder = new SvgTransformBuilder();
$renderer = new ShapeLayerRenderer($transformBuilder);
$builder = new SvgDocumentBuilder();

// Test counters and timing
$testCount = 0;
$passedTests = 0;
$failedTests = 0;

function runTest(string $testName, callable $testFunction): void {
    global $testCount, $passedTests, $failedTests;
    
    $testCount++;
    $startTime = microtime(true);
    
    try {
        $result = $testFunction();
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;
        
        if ($result) {
            echo "  ✅ {$testName} (" . number_format($duration, 2) . "ms)\n";
            $passedTests++;
        } else {
            echo "  ❌ {$testName}: Test returned false\n";
            $failedTests++;
        }
    } catch (Exception $e) {
        $endTime = microtime(true);
        echo "  ❌ {$testName}: " . $e->getMessage() . "\n";
        $failedTests++;
    }
}

// =============================================================================
// BASIC SHAPE TYPE TESTS
// =============================================================================

echo "🔧 Testing Basic Shape Types...\n";

runTest("Basic: Rectangle Shape", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'rectangle',
        'fill' => ['type' => 'solid', 'color' => '#ff0000']
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<rect') !== false &&
           strpos($xmlString, 'width="100"') !== false &&
           strpos($xmlString, 'height="100"') !== false &&
           strpos($xmlString, 'fill="#ff0000"') !== false;
});

runTest("Basic: Circle Shape", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'circle',
        'fill' => ['type' => 'solid', 'color' => '#00ff00']
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<circle') !== false &&
           strpos($xmlString, 'r="50"') !== false &&
           strpos($xmlString, 'cx="50"') !== false &&
           strpos($xmlString, 'cy="50"') !== false;
});

runTest("Basic: Ellipse Shape", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'ellipse',
        'fill' => ['type' => 'solid', 'color' => '#0000ff']
    ], 120, 80);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<ellipse') !== false &&
           strpos($xmlString, 'rx="60"') !== false &&
           strpos($xmlString, 'ry="40"') !== false;
});

runTest("Basic: Triangle Shape", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'triangle',
        'fill' => ['type' => 'solid', 'color' => '#ffff00']
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<polygon') !== false &&
           strpos($xmlString, 'points=') !== false &&
           strpos($xmlString, '50,0') !== false; // Top point
});

runTest("Basic: Polygon Shape (Hexagon)", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'polygon',
        'sides' => 6,
        'fill' => ['type' => 'solid', 'color' => '#ff00ff']
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Count points in polygon (hexagon should have 6 points)
    $pointsMatch = [];
    preg_match('/points="([^"]+)"/', $xmlString, $pointsMatch);
    $pointCount = count(explode(' ', trim($pointsMatch[1] ?? '')));
    
    return strpos($xmlString, '<polygon') !== false && $pointCount === 6;
});

runTest("Basic: Star Shape", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'star',
        'points' => 5,
        'innerRadius' => 0.4,
        'fill' => ['type' => 'solid', 'color' => '#00ffff']
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Star should have 10 points (5 outer + 5 inner)
    $pointsMatch = [];
    preg_match('/points="([^"]+)"/', $xmlString, $pointsMatch);
    $pointCount = count(explode(' ', trim($pointsMatch[1] ?? '')));
    
    return strpos($xmlString, '<polygon') !== false && $pointCount === 10;
});

runTest("Basic: Line Shape", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'line',
        'fill' => ['type' => 'solid', 'color' => '#888888']
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<line') !== false &&
           strpos($xmlString, 'x1="0"') !== false &&
           strpos($xmlString, 'x2="100"') !== false;
});

runTest("Basic: Arrow Shape", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'arrow',
        'fill' => ['type' => 'solid', 'color' => '#ff8800']
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<path') !== false &&
           strpos($xmlString, 'd=') !== false;
});

// =============================================================================
// FILL TYPE TESTS
// =============================================================================

echo "🎨 Testing Fill Types...\n";

runTest("Fill: Solid Color", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'rectangle',
        'fill' => [
            'type' => 'solid',
            'color' => '#123456',
            'opacity' => 0.8
        ]
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'fill="#123456"') !== false &&
           strpos($xmlString, 'fill-opacity="0.8"') !== false;
});

runTest("Fill: Linear Gradient", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(200, 200);
    
    $layer = createTestLayer('shape', [
        'shapeType' => 'rectangle',
        'fill' => [
            'type' => 'linear',
            'colors' => [
                ['color' => '#ff0000', 'stop' => 0.0],
                ['color' => '#0000ff', 'stop' => 1.0]
            ],
            'angle' => 45
        ]
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'fill="url(#gradient-') !== false;
});

runTest("Fill: Radial Gradient", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(200, 200);
    
    $layer = createTestLayer('shape', [
        'shapeType' => 'circle',
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
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'fill="url(#gradient-') !== false;
});

runTest("Fill: Pattern - Dots", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(200, 200);
    
    $layer = createTestLayer('shape', [
        'shapeType' => 'rectangle',
        'fill' => [
            'type' => 'pattern',
            'patternType' => 'dots',
            'size' => 10,
            'color' => '#ff0000',
            'backgroundColor' => '#ffffff'
        ]
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'fill="url(#pattern-') !== false;
});

runTest("Fill: Pattern - Stripes", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(200, 200);
    
    $layer = createTestLayer('shape', [
        'shapeType' => 'rectangle',
        'fill' => [
            'type' => 'pattern',
            'patternType' => 'stripes',
            'direction' => 'vertical',
            'color' => '#0000ff'
        ]
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'fill="url(#pattern-') !== false;
});

runTest("Fill: Pattern - Grid", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(200, 200);
    
    $layer = createTestLayer('shape', [
        'shapeType' => 'rectangle',
        'fill' => [
            'type' => 'pattern',
            'patternType' => 'grid',
            'lineWidth' => 2,
            'color' => '#333333'
        ]
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'fill="url(#pattern-') !== false;
});

// =============================================================================
// STROKE PROPERTY TESTS
// =============================================================================

echo "✏️ Testing Stroke Properties...\n";

runTest("Stroke: Basic Stroke", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'rectangle',
        'fill' => ['type' => 'solid', 'color' => '#ffffff'],
        'stroke' => '#ff0000',
        'strokeWidth' => 3
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'stroke="#ff0000"') !== false &&
           strpos($xmlString, 'stroke-width="3"') !== false;
});

runTest("Stroke: Stroke with Opacity", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'circle',
        'fill' => ['type' => 'solid', 'color' => '#ffffff'],
        'stroke' => '#0000ff',
        'strokeWidth' => 5,
        'strokeOpacity' => 0.6
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'stroke="#0000ff"') !== false &&
           strpos($xmlString, 'stroke-opacity="0.6"') !== false;
});

runTest("Stroke: Dashed Stroke", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'rectangle',
        'fill' => ['type' => 'solid', 'color' => '#ffffff'],
        'stroke' => '#00ff00',
        'strokeWidth' => 2,
        'strokeDashArray' => '5,5'
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'stroke-dasharray="5,5"') !== false;
});

runTest("Stroke: Line Cap Properties", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'line',
        'fill' => ['type' => 'solid', 'color' => '#ffffff'],
        'stroke' => '#ff00ff',
        'strokeWidth' => 8,
        'strokeLineCap' => 'round'
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'stroke-linecap="round"') !== false;
});

runTest("Stroke: Line Join Properties", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'triangle',
        'fill' => ['type' => 'solid', 'color' => '#ffffff'],
        'stroke' => '#ffff00',
        'strokeWidth' => 4,
        'strokeLineJoin' => 'bevel'
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'stroke-linejoin="bevel"') !== false;
});

// =============================================================================
// SHAPE-SPECIFIC PROPERTY TESTS
// =============================================================================

echo "⚙️ Testing Shape-Specific Properties...\n";

runTest("Rectangle: Corner Radius", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'rectangle',
        'cornerRadius' => 15,
        'fill' => ['type' => 'solid', 'color' => '#ff0000']
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'rx="15"') !== false &&
           strpos($xmlString, 'ry="15"') !== false;
});

runTest("Polygon: Custom Sides", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'polygon',
        'sides' => 8, // Octagon
        'fill' => ['type' => 'solid', 'color' => '#00ff00']
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Octagon should have 8 points
    $pointsMatch = [];
    preg_match('/points="([^"]+)"/', $xmlString, $pointsMatch);
    $pointCount = count(explode(' ', trim($pointsMatch[1] ?? '')));
    
    return $pointCount === 8;
});

runTest("Star: Custom Points and Inner Radius", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'star',
        'points' => 7,
        'innerRadius' => 0.3,
        'fill' => ['type' => 'solid', 'color' => '#0000ff']
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // 7-pointed star should have 14 points (7 outer + 7 inner)
    $pointsMatch = [];
    preg_match('/points="([^"]+)"/', $xmlString, $pointsMatch);
    $pointCount = count(explode(' ', trim($pointsMatch[1] ?? '')));
    
    return $pointCount === 14;
});

runTest("Line: Custom Coordinates", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'line',
        'x1' => 10,
        'y1' => 20,
        'x2' => 90,
        'y2' => 80,
        'fill' => ['type' => 'solid', 'color' => '#888888']
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'x1="10"') !== false &&
           strpos($xmlString, 'y1="20"') !== false &&
           strpos($xmlString, 'x2="90"') !== false &&
           strpos($xmlString, 'y2="80"') !== false;
});

// =============================================================================
// EFFECT TESTS
// =============================================================================

echo "✨ Testing Shape Effects...\n";

runTest("Effect: Shadow", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(200, 200);
    
    $layer = createTestLayer('shape', [
        'shapeType' => 'rectangle',
        'fill' => ['type' => 'solid', 'color' => '#ff0000'],
        'shadow' => [
            'enabled' => true,
            'offsetX' => 5,
            'offsetY' => 5,
            'blur' => 3,
            'color' => '#000000',
            'opacity' => 0.5
        ]
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'filter="url(#shape-shadow-') !== false;
});

runTest("Effect: Glow", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(200, 200);
    
    $layer = createTestLayer('shape', [
        'shapeType' => 'circle',
        'fill' => ['type' => 'solid', 'color' => '#0000ff'],
        'glow' => [
            'enabled' => true,
            'blur' => 8,
            'color' => '#ffffff',
            'opacity' => 0.8
        ]
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'filter="url(#shape-glow-') !== false;
});

// =============================================================================
// EDGE CASE TESTS
// =============================================================================

echo "🔍 Testing Edge Cases...\n";

runTest("Edge Case: Invalid Shape Type", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'invalid_shape',
        'fill' => ['type' => 'solid', 'color' => '#ff0000']
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should default to rectangle
    return strpos($xmlString, '<rect') !== false;
});

runTest("Edge Case: Zero Dimensions", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'rectangle',
        'fill' => ['type' => 'solid', 'color' => '#ff0000']
    ], 0, 0);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'width="0"') !== false &&
           strpos($xmlString, 'height="0"') !== false;
});

runTest("Edge Case: Very Large Dimensions", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'circle',
        'fill' => ['type' => 'solid', 'color' => '#00ff00']
    ], 10000, 10000);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, 'r="5000"') !== false;
});

runTest("Edge Case: Polygon with Minimum Sides", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'polygon',
        'sides' => 3, // Triangle via polygon
        'fill' => ['type' => 'solid', 'color' => '#ffff00']
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should have 3 points
    $pointsMatch = [];
    preg_match('/points="([^"]+)"/', $xmlString, $pointsMatch);
    $pointCount = count(explode(' ', trim($pointsMatch[1] ?? '')));
    
    return $pointCount === 3;
});

runTest("Edge Case: Polygon with Maximum Sides", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'polygon',
        'sides' => 20, // Maximum allowed
        'fill' => ['type' => 'solid', 'color' => '#ff00ff']
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should have 20 points
    $pointsMatch = [];
    preg_match('/points="([^"]+)"/', $xmlString, $pointsMatch);
    $pointCount = count(explode(' ', trim($pointsMatch[1] ?? '')));
    
    return $pointCount === 20;
});

runTest("Edge Case: Invalid Fill Type", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'rectangle',
        'fill' => ['type' => 'invalid_fill_type']
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should have some default fill
    return strpos($xmlString, 'fill=') !== false;
});

runTest("Edge Case: Empty Gradient Colors", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(200, 200);
    
    $layer = createTestLayer('shape', [
        'shapeType' => 'rectangle',
        'fill' => [
            'type' => 'linear',
            'colors' => [] // Empty colors array
        ]
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should handle gracefully
    return strpos($xmlString, '<rect') !== false;
});

// =============================================================================
// COMPLEX INTEGRATION TESTS
// =============================================================================

echo "🧩 Testing Complex Integration Scenarios...\n";

runTest("Complex: Multi-Effect Shape", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(300, 300);
    
    $layer = createTestLayer('shape', [
        'shapeType' => 'star',
        'points' => 6,
        'innerRadius' => 0.4,
        'fill' => [
            'type' => 'linear',
            'colors' => [
                ['color' => '#ff0000', 'stop' => 0.0],
                ['color' => '#ffff00', 'stop' => 0.5],
                ['color' => '#ff8800', 'stop' => 1.0]
            ],
            'angle' => 90
        ],
        'stroke' => '#000000',
        'strokeWidth' => 3,
        'strokeDashArray' => '10,5',
        'shadow' => [
            'enabled' => true,
            'offsetX' => 8,
            'offsetY' => 8,
            'blur' => 5,
            'color' => '#333333',
            'opacity' => 0.7
        ]
    ], 150, 150);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<polygon') !== false &&
           strpos($xmlString, 'fill="url(#gradient-') !== false &&
           strpos($xmlString, 'stroke="#000000"') !== false &&
           strpos($xmlString, 'stroke-dasharray="10,5"') !== false &&
           strpos($xmlString, 'filter="url(#shape-shadow-') !== false;
});

runTest("Complex: Rounded Rectangle with Pattern", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(200, 200);
    
    $layer = createTestLayer('shape', [
        'shapeType' => 'rectangle',
        'cornerRadius' => 20,
        'fill' => [
            'type' => 'pattern',
            'patternType' => 'dots',
            'size' => 8,
            'color' => '#0066cc',
            'backgroundColor' => '#ffffff'
        ],
        'stroke' => '#003366',
        'strokeWidth' => 2
    ], 120, 80);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    return strpos($xmlString, '<rect') !== false &&
           strpos($xmlString, 'rx="20"') !== false &&
           strpos($xmlString, 'fill="url(#pattern-') !== false &&
           strpos($xmlString, 'stroke="#003366"') !== false;
});

// =============================================================================
// ERROR RECOVERY TESTS
// =============================================================================

echo "🔧 Testing Error Recovery...\n";

runTest("Error Recovery: Malformed Properties", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [
        'shapeType' => 'rectangle',
        'fill' => 'invalid_fill_format', // Should be array
        'stroke' => 'invalid_color_#gggggg', // Invalid color format
        'strokeWidth' => 'invalid' // Should be number
    ], 100, 100);
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should render something without crashing
    return strpos($xmlString, '<rect') !== false;
});

runTest("Error Recovery: Missing Properties", function() use ($renderer, $builder) {
    $layer = createTestLayer('shape', [], 100, 100); // Empty properties
    
    $svgElement = $renderer->render($layer, $builder);
    $xmlString = $builder->saveDocument($svgElement);
    
    // Should render default rectangle
    return strpos($xmlString, '<rect') !== false;
});

// =============================================================================
// PERFORMANCE TESTS
// =============================================================================

echo "⚡ Testing Performance...\n";

runTest("Performance: Many Shapes Rendering", function() use ($renderer, $builder) {
    $svg = $builder->createDocument(1000, 1000);
    $startTime = microtime(true);
    
    // Render 100 shapes of different types
    for ($i = 0; $i < 100; $i++) {
        $shapeTypes = ['rectangle', 'circle', 'ellipse', 'triangle', 'polygon', 'star'];
        $shapeType = $shapeTypes[$i % count($shapeTypes)];
        
        $layer = createTestLayer('shape', [
            'shapeType' => $shapeType,
            'fill' => ['type' => 'solid', 'color' => sprintf('#%06x', rand(0, 0xFFFFFF))]
        ], 50, 50);
        
        $renderer->render($layer, $builder);
    }
    
    $endTime = microtime(true);
    $duration = ($endTime - $startTime) * 1000;
    
    // Should complete within reasonable time (< 1000ms)
    return $duration < 1000;
});

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

function createTestLayer(string $type, array $properties = [], ?float $width = null, ?float $height = null): Layer
{
    $layer = new Layer();
    $layer->setType($type);
    $layer->setProperties($properties);
    $layer->setWidth($width);
    $layer->setHeight($height);
    $layer->setX(0);
    $layer->setY(0);
    $layer->setZIndex(1);
    $layer->setVisible(true);
    $layer->setOpacity(1.0);
    
    return $layer;
}

// =============================================================================
// COMPLETE TEST RESULTS AND SVG OUTPUT
// =============================================================================

echo "\n" . str_repeat("=", 70) . "\n";
echo "🎯 SHAPE LAYER TEST RESULTS\n";
echo str_repeat("=", 70) . "\n";
echo "Total Tests: {$testCount}\n";
echo "✅ Passed: {$passedTests}\n";
echo "❌ Failed: {$failedTests}\n";
echo "Success Rate: " . number_format(($passedTests / $testCount) * 100, 1) . "%\n\n";

if ($failedTests === 0) {
    echo "🎉 ALL TESTS PASSED! The shape layer system is fully functional.\n\n";
} else {
    echo "⚠️  Some tests failed. Please review the failed test cases above.\n\n";
}

// =============================================================================
// GENERATE COMPREHENSIVE SHAPE GALLERY SVG
// =============================================================================

echo "📋 Generating comprehensive shape gallery...\n";

try {
    $galleryBuilder = new SvgDocumentBuilder();
    $gallerySvg = $galleryBuilder->createDocument(1200, 950);
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
    
    $shapeConfigs = [
        // Row 1: Basic shapes
        ['type' => 'rectangle', 'x' => 50, 'y' => 50, 'props' => ['fill' => ['type' => 'solid', 'color' => '#ff6b6b']]],
        ['type' => 'circle', 'x' => 200, 'y' => 50, 'props' => ['fill' => ['type' => 'solid', 'color' => '#4ecdc4']]],
        ['type' => 'ellipse', 'x' => 350, 'y' => 50, 'props' => ['fill' => ['type' => 'solid', 'color' => '#45b7d1']]],
        ['type' => 'triangle', 'x' => 500, 'y' => 50, 'props' => ['fill' => ['type' => 'solid', 'color' => '#f9ca24']]],
        
        // Row 2: Complex shapes
        ['type' => 'polygon', 'x' => 50, 'y' => 200, 'props' => ['sides' => 6, 'fill' => ['type' => 'solid', 'color' => '#f0932b']]],
        ['type' => 'star', 'x' => 200, 'y' => 200, 'props' => ['points' => 5, 'fill' => ['type' => 'solid', 'color' => '#eb4d4b']]],
        ['type' => 'line', 'x' => 350, 'y' => 200, 'props' => ['stroke' => '#6c5ce7', 'strokeWidth' => 8]],
        ['type' => 'arrow', 'x' => 500, 'y' => 200, 'props' => ['fill' => ['type' => 'solid', 'color' => '#a29bfe']]],
        
        // Row 3: Gradients and effects
        ['type' => 'rectangle', 'x' => 50, 'y' => 350, 'props' => [
            'cornerRadius' => 20,
            'fill' => [
                'type' => 'linear',
                'colors' => [
                    ['color' => '#ff9a56', 'stop' => 0.0],
                    ['color' => '#ff6b9d', 'stop' => 1.0]
                ]
            ]
        ]],
        ['type' => 'circle', 'x' => 200, 'y' => 350, 'props' => [
            'fill' => [
                'type' => 'radial',
                'colors' => [
                    ['color' => '#ffffff', 'stop' => 0.0],
                    ['color' => '#3742fa', 'stop' => 1.0]
                ]
            ]
        ]],
        ['type' => 'star', 'x' => 350, 'y' => 350, 'props' => [
            'points' => 8,
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
        ]],
        ['type' => 'polygon', 'x' => 500, 'y' => 350, 'props' => [
            'sides' => 8,
            'fill' => ['type' => 'solid', 'color' => '#48dbfb'],
            'glow' => [
                'enabled' => true,
                'blur' => 15,
                'color' => '#0abde3',
                'opacity' => 0.8
            ]
        ]],
        
        // Row 4: Pattern fills
        ['type' => 'rectangle', 'x' => 50, 'y' => 500, 'props' => [
            'fill' => [
                'type' => 'pattern',
                'patternType' => 'dots',
                'size' => 8,
                'color' => '#ff6b6b',
                'backgroundColor' => '#ffffff'
            ]
        ]],
        ['type' => 'circle', 'x' => 200, 'y' => 500, 'props' => [
            'fill' => [
                'type' => 'pattern',
                'patternType' => 'stripes',
                'direction' => 'diagonal',
                'size' => 12,
                'color' => '#4ecdc4',
                'backgroundColor' => '#f8f9fa'
            ]
        ]],
        ['type' => 'triangle', 'x' => 350, 'y' => 500, 'props' => [
            'fill' => [
                'type' => 'pattern',
                'patternType' => 'grid',
                'size' => 15,
                'lineWidth' => 2,
                'color' => '#45b7d1',
                'backgroundColor' => '#ffffff'
            ]
        ]]
    ];
    
    $layerCounter = 1;
    foreach ($shapeConfigs as $config) {
        $layer = createTestLayer('shape', array_merge([
            'shapeType' => $config['type']
        ], $config['props']), 120, 120);
        
        // Set proper layer name and position
        $layer->setName('gallery-shape-' . $layerCounter);
        $layer->setX($config['x']);
        $layer->setY($config['y']);
        
        $shapeElement = $renderer->render($layer, $galleryBuilder);
        $gallerySvg->appendChild($shapeElement);
        $layerCounter++;
    }
    
    // Process all definitions (gradients, filters, etc.) to ensure they're included
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
    $title->appendChild($galleryBuilder->createText('Comprehensive Shape Gallery', $gallerySvg->ownerDocument));
    $gallerySvg->appendChild($title);
    
    // Save the gallery
    $outputPath = __DIR__ . '/output/comprehensive_shape_gallery.svg';
    if (!is_dir(dirname($outputPath))) {
        mkdir(dirname($outputPath), 0755, true);
    }
    
    file_put_contents($outputPath, $galleryBuilder->saveDocument($gallerySvg));
    $fileSize = filesize($outputPath);
    
    echo "✅ Shape gallery saved: {$outputPath} (" . number_format($fileSize) . " bytes)\n";
    
} catch (Exception $e) {
    echo "❌ Failed to generate shape gallery: " . $e->getMessage() . "\n";
}

echo "\n🔶 SHAPE LAYER TESTING COMPLETE!\n";
