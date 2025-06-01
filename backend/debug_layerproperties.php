<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\DTO\UpdateLayerRequestDTO;
use App\DTO\CreateLayerRequestDTO;

function debugLayerProperties() {
    $classes = [
        UpdateLayerRequestDTO::class,
        CreateLayerRequestDTO::class
    ];
    
    foreach ($classes as $className) {
        echo "=== {$className} ===\n";
        
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        
        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                if ($param->getName() === 'properties') {
                    echo "Parameter name: " . $param->getName() . "\n";
                    echo "Type: " . ($param->getType() ? $param->getType()->getName() : 'null') . "\n";
                    echo "Is builtin: " . ($param->getType() && $param->getType()->isBuiltin() ? 'true' : 'false') . "\n";
                    echo "Has default: " . ($param->isDefaultValueAvailable() ? 'true' : 'false') . "\n";
                    echo "Allows null: " . ($param->allowsNull() ? 'true' : 'false') . "\n";
                    
                    // Check if class exists
                    $typeName = $param->getType()->getName();
                    echo "Class exists: " . (class_exists($typeName) ? 'true' : 'false') . "\n";
                    
                    if (class_exists($typeName)) {
                        $typeReflection = new ReflectionClass($typeName);
                        echo "Is abstract: " . ($typeReflection->isAbstract() ? 'true' : 'false') . "\n";
                        echo "Is interface: " . ($typeReflection->isInterface() ? 'true' : 'false') . "\n";
                    }
                    echo "\n";
                    break;
                }
            }
        }
    }
}

debugLayerProperties();
