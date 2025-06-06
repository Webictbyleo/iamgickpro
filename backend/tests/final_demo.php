<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entity\Layer;
use App\Service\Svg\LayerRenderer\GroupLayerRenderer;
use App\Service\Svg\LayerRenderer\ShapeLayerRenderer;
use App\Service\Svg\SvgDocumentBuilder;
use App\Service\Svg\SvgTransformBuilder;

function createLayer(string $type, string $name): Layer {
    $layer = new Layer();
    $layer->setType($type);
    $layer->setName($name);
    $layer->setVisible(true);
    $layer->setOpacity(1.0);
    $layer->setZIndex(0);
    $layer->setX(0);
    $layer->setY(0);
    $layer->setWidth(100);
    $layer->setHeight(60);
    return $layer;
}

// Create renderers
$transformBuilder = new SvgTransformBuilder();
$shapeRenderer = new ShapeLayerRenderer($transformBuilder);
$groupRenderer = new GroupLayerRenderer($transformBuilder, [$shapeRenderer]);
$builder = new SvgDocumentBuilder();

// Create group with child
$group = createLayer('group', 'Test Group');
$child = createLayer('shape', 'Child Shape');
$child->setProperties(['shapeType' => 'rectangle']);

// NEW WAY: Use Entity relationship
$group->addChild($child);

// Render automatically
$svgDoc = $builder->createDocument(200, 150);
$groupElement = $groupRenderer->render($group, $builder);

if ($groupElement) {
    $svgDoc->appendChild($groupElement);
    $content = $svgDoc->ownerDocument->saveXML($svgDoc);
    file_put_contents(__DIR__ . '/final_demo.svg', $content);
    
    // Count children
    $childCount = $groupElement->getElementsByTagName('rect')->length;
    
    print "SUCCESS: GroupLayerRenderer Enhanced!\n";
    print "- Group contains {$group->getChildren()->count()} Entity children\n";
    print "- Rendered {$childCount} SVG elements automatically\n";
    print "- No manual DOM manipulation needed\n";
    print "- Saved to final_demo.svg\n";
} else {
    print "ERROR: Failed to render group\n";
}
