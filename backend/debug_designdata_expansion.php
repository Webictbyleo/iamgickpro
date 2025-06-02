<?php

require_once 'vendor/autoload.php';

use App\DTO\CreateDesignRequestDTO;
use App\DTO\ValueObject\DesignData;

// Test type extraction for DesignData
$reflection = new ReflectionClass(CreateDesignRequestDTO::class);
$constructor = $reflection->getConstructor();

if ($constructor) {
    foreach ($constructor->getParameters() as $param) {
        if ($param->getName() === 'data') {
            echo "Parameter name: " . $param->getName() . "\n";
            
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType) {
                echo "Reflection type: " . $type->getName() . "\n";
            }
            
            // Check if DesignData class exists and can be reflected
            echo "DesignData class exists: " . (class_exists(DesignData::class) ? 'yes' : 'no') . "\n";
            
            if (class_exists(DesignData::class)) {
                $designDataReflection = new ReflectionClass(DesignData::class);
                echo "DesignData constructor parameters:\n";
                
                $constructor = $designDataReflection->getConstructor();
                if ($constructor) {
                    foreach ($constructor->getParameters() as $prop) {
                        $propType = $prop->getType();
                        $propTypeName = $propType instanceof ReflectionNamedType ? $propType->getName() : 'unknown';
                        echo "  - {$prop->getName()}: {$propTypeName}\n";
                    }
                }
            }
            
            break;
        }
    }
}
