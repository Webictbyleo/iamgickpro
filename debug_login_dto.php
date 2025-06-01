<?php
require_once __DIR__ . '/backend/vendor/autoload.php';

$reflection = new \ReflectionClass('App\DTO\LoginRequestDTO');

echo "=== LoginRequestDTO Analysis ===\n";
echo "Class name: " . $reflection->getName() . "\n";
echo "Has constructor: " . ($reflection->getConstructor() ? 'yes' : 'no') . "\n";

if ($reflection->getConstructor()) {
    $params = $reflection->getConstructor()->getParameters();
    echo "Constructor params count: " . count($params) . "\n";
    foreach ($params as $param) {
        echo "  - {$param->getName()}\n";
    }
}

$properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
echo "Public properties count: " . count($properties) . "\n";
foreach ($properties as $property) {
    echo "  - {$property->getName()} (promoted: " . ($property->isPromoted() ? 'yes' : 'no') . ")\n";
}
