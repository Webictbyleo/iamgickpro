<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entity\Layer;
use App\Service\Svg\LayerRenderer\VideoLayerRenderer;
use App\Service\Svg\SvgDocumentBuilder;
use App\Service\Svg\SvgTransformBuilder;

echo "=== Video Layer Debug Test ===\n";

// Initialize components
$transformBuilder = new SvgTransformBuilder();
$renderer = new VideoLayerRenderer($transformBuilder);
$builder = new SvgDocumentBuilder();

// Create test layer
$layer = new Layer();
$layer->setType('video');
$layer->setWidth(100);
$layer->setHeight(100);
$layer->setX(0);
$layer->setY(0);
$layer->setZIndex(1);
$layer->setVisible(true);
$layer->setOpacity(1.0);

echo "Layer created successfully\n";

// Test if renderer can render this layer
if ($renderer->canRender($layer)) {
    echo "Renderer can handle video layer\n";
    
    try {
        $element = $renderer->render($layer, $builder);
        echo "Rendering succeeded\n";
        
        $svg = $element->ownerDocument->saveXML($element);
        echo "SVG extraction succeeded\n";
        
        echo "SVG content:\n";
        echo $svg . "\n";
        
    } catch (Exception $e) {
        echo "Rendering failed: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
} else {
    echo "Renderer cannot handle video layer\n";
}

echo "=== Test Complete ===\n";
