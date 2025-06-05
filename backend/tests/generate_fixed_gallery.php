<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entity\Layer;
use App\Service\Svg\LayerRenderer\ShapeLayerRenderer;
use App\Service\Svg\SvgDocumentBuilder;
use App\Service\Svg\SvgTransformBuilder;

echo "🔧 GENERATING FIXED COMPREHENSIVE SHAPE GALLERY\n";
echo str_repeat("=", 60) . "\n";

// Initialize components
$transformBuilder = new SvgTransformBuilder();
$renderer = new ShapeLayerRenderer($transformBuilder);
$builder = new SvgDocumentBuilder();

function createLayer(string $type, array $properties = [], ?float $width = null, ?float $height = null, ?string $id = null): Layer
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
    
    if ($id) {
        $layer->setId($id);
    }
    
    return $layer;
}

try {
    // Create main SVG document
    $gallerySvg = $builder->createDocument(1200, 800);
    $gallerySvg->setAttribute('style', 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);');
    
    // Get the main defs section
    $defs = $builder->addDefinitions($gallerySvg);
    
    // Create background gradient
    $bgGradient = $builder->createLinearGradient('bg-gradient', [
        ['offset' => '0%', 'color' => '#667eea'],
        ['offset' => '100%', 'color' => '#764ba2']
    ], [], $gallerySvg->ownerDocument);
    $defs->appendChild($bgGradient);
    
    // Create background
    $background = $builder->createElement('rect');
    $background->setAttribute('width', '100%');
    $background->setAttribute('height', '100%');
    $background->setAttribute('fill', 'url(#bg-gradient)');
    $gallerySvg->appendChild($background);
    
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
                    ['color' => '#667eea', 'stop' => 1.0]
                ]
            ]
        ]],
        ['type' => 'star', 'x' => 350, 'y' => 350, 'props' => [
            'points' => 12,
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
        ]]
    ];
    
    $layerCounter = 1;
    foreach ($shapeConfigs as $config) {
        $layer = createLayer('shape', array_merge([
            'shapeType' => $config['type']
        ], $config['props']), 120, 120, 'gallery-shape-' . $layerCounter);
        
        $layer->setX($config['x']);
        $layer->setY($config['y']);
        
        echo "Rendering shape {$layerCounter}: {$config['type']}...\n";
        $shapeElement = $renderer->render($layer, $builder);
        $gallerySvg->appendChild($shapeElement);
        $layerCounter++;
    }
    
    // Process all definitions to ensure gradients and filters are included
    echo "Processing definitions...\n";
    $builder->processDefinitions($gallerySvg);
    
    // Add title
    $title = $builder->createElement('text');
    $title->setAttribute('x', '600');
    $title->setAttribute('y', '40');
    $title->setAttribute('text-anchor', 'middle');
    $title->setAttribute('font-family', 'Arial, sans-serif');
    $title->setAttribute('font-size', '28');
    $title->setAttribute('font-weight', 'bold');
    $title->setAttribute('fill', 'white');
    $title->setAttribute('stroke', 'rgba(0,0,0,0.3)');
    $title->setAttribute('stroke-width', '1');
    $title->appendChild($builder->createText('Fixed Comprehensive Shape Gallery', $gallerySvg->ownerDocument));
    $gallerySvg->appendChild($title);
    
    // Save the fixed gallery
    $outputPath = __DIR__ . '/output/fixed_comprehensive_shape_gallery.svg';
    if (!is_dir(dirname($outputPath))) {
        mkdir(dirname($outputPath), 0755, true);
    }
    
    $svgContent = $builder->saveDocument($gallerySvg);
    file_put_contents($outputPath, $svgContent);
    $fileSize = filesize($outputPath);
    
    echo "\n✅ Fixed shape gallery saved: {$outputPath} (" . number_format($fileSize) . " bytes)\n";
    
    // Validate the SVG
    echo "\n🔍 Validating SVG structure...\n";
    
    // Check for missing gradients
    $gradientRefs = [];
    $gradientDefs = [];
    
    if (preg_match_all('/url\(#([^)]+)\)/', $svgContent, $matches)) {
        $gradientRefs = $matches[1];
    }
    
    if (preg_match_all('/id="([^"]*gradient[^"]*)"/', $svgContent, $matches)) {
        $gradientDefs = $matches[1];
    }
    
    $missingGradients = array_diff($gradientRefs, $gradientDefs);
    
    if (empty($missingGradients)) {
        echo "✅ All referenced gradients are defined\n";
    } else {
        echo "❌ Missing gradients: " . implode(', ', $missingGradients) . "\n";
    }
    
    // Check for proper layer IDs
    $layerIds = [];
    if (preg_match_all('/id="([^"]*layer[^"]*)"/', $svgContent, $matches)) {
        $layerIds = $matches[1];
    }
    
    echo "📊 Layer IDs found: " . count($layerIds) . "\n";
    
    // Count defs sections
    $defsCount = substr_count($svgContent, '<defs>');
    echo "📋 Defs sections: {$defsCount}\n";
    
    echo "\n🎉 Gallery generation complete!\n";
    
} catch (Exception $e) {
    echo "❌ Failed to generate fixed gallery: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
