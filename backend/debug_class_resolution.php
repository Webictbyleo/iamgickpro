<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

// Manually include the LayerProperties file to ensure all classes are loaded
require_once __DIR__ . '/src/DTO/ValueObject/LayerProperties.php';

// Debug the class resolution issue
function debugClassResolution() {
    $types = [
        'TextLayerProperties',
        'ImageLayerProperties', 
        'ShapeLayerProperties',
        'App\\DTO\\ValueObject\\TextLayerProperties',
        'App\\DTO\\ValueObject\\ImageLayerProperties',
        'App\\DTO\\ValueObject\\ShapeLayerProperties'
    ];
    
    foreach ($types as $type) {
        echo "Testing type: {$type}\n";
        
        // Test if class exists directly
        if (class_exists($type)) {
            echo "  ✓ class_exists() = true\n";
            
            try {
                $reflection = new ReflectionClass($type);
                echo "  ✓ ReflectionClass created successfully\n";
                echo "  ✓ Is abstract: " . ($reflection->isAbstract() ? 'true' : 'false') . "\n";
                echo "  ✓ Constructor exists: " . ($reflection->getConstructor() ? 'true' : 'false') . "\n";
                
                if ($reflection->getConstructor()) {
                    $params = $reflection->getConstructor()->getParameters();
                    echo "  ✓ Constructor parameters: " . count($params) . "\n";
                    
                    // Test property extraction similar to the API docs generator
                    foreach ($params as $param) {
                        echo "    - " . $param->getName() . ": " . ($param->getType() ? $param->getType()->getName() : 'mixed');
                        if ($param->isDefaultValueAvailable()) {
                            echo " = " . var_export($param->getDefaultValue(), true);
                        }
                        echo "\n";
                    }
                }
            } catch (Exception $e) {
                echo "  ✗ ReflectionClass error: " . $e->getMessage() . "\n";
            }
        } else {
            echo "  ✗ class_exists() = false\n";
        }
        
        echo "\n";
    }
}

debugClassResolution();
