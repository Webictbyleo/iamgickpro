<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

class MockApiDocGenerator {
    private function isValueObjectClass(string $type): bool
    {
        $valueObjectTypes = [
            'Tag', 'Transform', 'LayerProperties', 'UserSettings', 
            'ProjectSettings', 'MediaMetadata', 'LayerUpdate', 'DesignData',
            'TextLayerProperties', 'ImageLayerProperties', 'ShapeLayerProperties'
        ];
        
        if (in_array($type, $valueObjectTypes)) {
            return true;
        }
        
        if (str_starts_with($type, 'App\\DTO\\ValueObject\\')) {
            return class_exists($type);
        }
        
        $fqn = 'App\\DTO\\ValueObject\\' . $type;
        return class_exists($fqn);
    }
    
    private function convertPhpTypeToTypeScript(string $phpType): string
    {
        return match($phpType) {
            'int' => 'number',
            'float' => 'number',
            'bool' => 'boolean',
            'string' => 'string',
            'array' => 'any[]',
            'object' => 'object',
            'mixed' => 'any',
            'null' => 'null',
            'DateTime', 'DateTimeImmutable', 'DateTimeInterface' => 'string',
            default => $this->isValueObjectClass($phpType) ? $phpType :
                (str_ends_with($phpType, 'DTO') ? $phpType : 'any')
        };
    }
    
    private function extractTypeInfo(?\ReflectionParameter $parameter = null, ?\ReflectionProperty $property = null): string
    {
        $reflectionType = null;
        
        if ($parameter) {
            $reflectionType = $parameter->getType();
        } elseif ($property) {
            $reflectionType = $property->getType();
        }
        
        if (!$reflectionType) {
            return 'any';
        }
        
        $typeName = $reflectionType->getName();
        echo "extractTypeInfo: typeName = {$typeName}\n";
        
        // For DTO types, return fully qualified name as-is
        if (str_ends_with($typeName, 'DTO') && class_exists($typeName)) {
            echo "extractTypeInfo: returning DTO type: {$typeName}\n";
            return $typeName;
        }
        
        // Check if it's a value object
        if ($this->isValueObjectClass($typeName)) {
            echo "extractTypeInfo: returning value object type: {$typeName}\n";
            return $typeName;
        }
        
        $converted = $this->convertPhpTypeToTypeScript($typeName);
        echo "extractTypeInfo: converted to: {$converted}\n";
        return $converted;
    }
    
    public function testExtractTypeInfo() {
        $reflection = new ReflectionClass(\App\DTO\UpdateLayerRequestDTO::class);
        $constructor = $reflection->getConstructor();
        
        foreach ($constructor->getParameters() as $param) {
            if ($param->getName() === 'properties') {
                echo "Testing parameter: " . $param->getName() . "\n";
                $result = $this->extractTypeInfo($param);
                echo "Final result: {$result}\n\n";
                break;
            }
        }
    }
}

(new MockApiDocGenerator())->testExtractTypeInfo();
