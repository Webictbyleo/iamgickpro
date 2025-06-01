<?php

require_once __DIR__ . '/backend/vendor/autoload.php';

use App\DTO\LoginRequestDTO;

echo "=== DEBUGGING LoginRequestDTO ===\n\n";

$reflection = new ReflectionClass(LoginRequestDTO::class);

echo "Class: " . $reflection->getName() . "\n";
echo "Constructor exists: " . ($reflection->getConstructor() ? 'YES' : 'NO') . "\n\n";

echo "=== PROPERTIES ===\n";
$properties = $reflection->getProperties();
foreach ($properties as $property) {
    echo "Property: " . $property->getName() . "\n";
    echo "  - Type: " . ($property->getType() ? $property->getType() : 'none') . "\n";
    echo "  - Is readonly: " . ($property->isReadOnly() ? 'YES' : 'NO') . "\n";
    echo "  - Visibility: " . ($property->isPublic() ? 'public' : ($property->isPrivate() ? 'private' : 'protected')) . "\n";
    
    $attributes = $property->getAttributes();
    echo "  - Attributes: " . count($attributes) . "\n";
    foreach ($attributes as $attr) {
        echo "    * " . $attr->getName() . "\n";
        $args = $attr->getArguments();
        if (!empty($args)) {
            echo "      Args: " . json_encode($args) . "\n";
        }
    }
    echo "\n";
}

echo "=== CONSTRUCTOR PARAMETERS ===\n";
$constructor = $reflection->getConstructor();
if ($constructor) {
    $params = $constructor->getParameters();
    foreach ($params as $param) {
        echo "Parameter: " . $param->getName() . "\n";
        echo "  - Type: " . ($param->getType() ? $param->getType() : 'none') . "\n";
        echo "  - Is promoted: " . ($param->isPromoted() ? 'YES' : 'NO') . "\n";
        
        $attributes = $param->getAttributes();
        echo "  - Attributes: " . count($attributes) . "\n";
        foreach ($attributes as $attr) {
            echo "    * " . $attr->getName() . "\n";
        }
        echo "\n";
    }
}

// Test the current extraction logic
echo "=== TESTING extractDtoPropertiesWithInheritance ===\n";

function extractDtoPropertiesWithInheritance($className) {
    $properties = [];
    $processedProperties = [];
    
    $reflection = new ReflectionClass($className);
    
    // Check constructor parameters (promoted properties)
    $constructor = $reflection->getConstructor();
    if ($constructor) {
        foreach ($constructor->getParameters() as $param) {
            $paramName = $param->getName();
            echo "Processing constructor param: {$paramName}, promoted: " . ($param->isPromoted() ? 'YES' : 'NO') . "\n";
            
            if ($param->isPromoted()) {
                echo "  -> Adding promoted property to processedProperties\n";
                $processedProperties[] = $paramName;
                
                $properties[] = [
                    'name' => $paramName,
                    'type' => $param->getType() ? (string) $param->getType() : 'mixed',
                    'nullable' => $param->getType() ? $param->getType()->allowsNull() : false,
                    'hasDefault' => $param->isDefaultValueAvailable(),
                    'description' => ucfirst($paramName) . ' value',
                    'validation' => []
                ];
            } else {
                echo "  -> Parameter not promoted, skipping for now\n";
            }
        }
    }
    
    echo "ProcessedProperties after constructor: " . json_encode($processedProperties) . "\n";
    
    // Get all class properties (including inherited ones)
    $reflectionProperties = $reflection->getProperties();
    echo "Total class properties found: " . count($reflectionProperties) . "\n";
    
    foreach ($reflectionProperties as $property) {
        $propertyName = $property->getName();
        echo "Processing class property: {$propertyName}\n";
        
        if (!in_array($propertyName, $processedProperties)) {
            echo "  -> Not in processedProperties, adding\n";
            $properties[] = [
                'name' => $propertyName,
                'type' => $property->getType() ? (string) $property->getType() : 'mixed',
                'nullable' => $property->getType() ? $property->getType()->allowsNull() : false,
                'hasDefault' => false,
                'description' => ucfirst($propertyName) . ' value',
                'validation' => []
            ];
        } else {
            echo "  -> Already in processedProperties, skipping\n";
        }
    }
    
    return $properties;
}

$result = extractDtoPropertiesWithInheritance(LoginRequestDTO::class);
echo "\nFINAL RESULT:\n";
print_r($result);
