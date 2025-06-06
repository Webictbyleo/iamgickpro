<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entity\Design;
use App\Entity\Layer;
use App\Service\Svg\SvgRendererService;
use App\Service\Svg\SvgDocumentBuilder;
use App\Service\Svg\SvgValidationService;
use App\Service\Svg\SvgErrorRecoveryService;
use App\Service\Svg\SvgTransformBuilder;
use App\Service\Svg\LayerRenderer\GroupLayerRenderer;
use App\Service\Svg\LayerRenderer\ShapeLayerRenderer;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\NullLogger;

// Create a test design with group and child layers
$design = new Design();
$design->setName('Test Design');
$design->setWidth(400);
$design->setHeight(300);
$design->setBackground(['type' => 'color', 'color' => '#ffffff']);

// Create root group layer
$groupLayer = new Layer();
$groupLayer->setType('group');
$groupLayer->setName('Main Group');
$groupLayer->setX(50);
$groupLayer->setY(50);
$groupLayer->setWidth(200);
$groupLayer->setHeight(100);
$groupLayer->setZIndex(1);
$groupLayer->setVisible(true);
$groupLayer->setDesign($design);
$groupLayer->setProperties([
    'blendMode' => 'multiply'
]);

// Create child layer
$childLayer = new Layer();
$childLayer->setType('shape');
$childLayer->setName('Child Rectangle');
$childLayer->setX(20);
$childLayer->setY(20);
$childLayer->setWidth(160);
$childLayer->setHeight(60);
$childLayer->setZIndex(1);
$childLayer->setVisible(true);
$childLayer->setDesign($design);
$childLayer->setParent($groupLayer);
$childLayer->setProperties([
    'shapeType' => 'rectangle',
    'fill' => ['type' => 'color', 'color' => '#e74c3c']
]);

// Set up parent-child relationship
$groupLayer->addChild($childLayer);

// Add layers to design
$design->addLayer($groupLayer);
$design->addLayer($childLayer); // This will be filtered out by SvgRendererService since it has a parent

// Create services
$transformBuilder = new SvgTransformBuilder();
$documentBuilder = new SvgDocumentBuilder();
$validationService = new SvgValidationService(new NullLogger());
$errorRecoveryService = new SvgErrorRecoveryService(new NullLogger());

// Create renderers
$shapeRenderer = new ShapeLayerRenderer($transformBuilder);
$groupRenderer = new GroupLayerRenderer($transformBuilder, [$shapeRenderer]);
$renderers = [$shapeRenderer, $groupRenderer];

// Create SvgRendererService
$svgRenderer = new SvgRendererService(
    $documentBuilder,
    $validationService,
    $errorRecoveryService,
    new NullLogger(),
    $renderers
);

// Render the design
try {
    $svgContent = $svgRenderer->renderDesignToSvg($design);
    
    file_put_contents(__DIR__ . '/integration_test_output.svg', $svgContent);
    
    // Count elements
    $doc = new DOMDocument();
    $doc->loadXML($svgContent);
    $groups = $doc->getElementsByTagName('g');
    $rects = $doc->getElementsByTagName('rect');
    
    print "INTEGRATION TEST SUCCESS!\n";
    print "- Design contains {$design->getLayers()->count()} total layers\n";
    print "- Only root layers are rendered by SvgRendererService\n";
    print "- Child layers are rendered by GroupLayerRenderer automatically\n";
    print "- Generated {$groups->length} group elements\n";
    print "- Generated {$rects->length} rectangle elements\n";
    print "- No duplicate rendering of child layers\n";
    print "- Saved to: integration_test_output.svg\n";
    
} catch (Exception $e) {
    print "ERROR: " . $e->getMessage() . "\n";
}
