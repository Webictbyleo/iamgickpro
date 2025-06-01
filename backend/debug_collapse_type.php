<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

class MockCollapseType {
    private array $collapsedTypes = [];
    
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
    
    private function isCollapsibleType(string $type): bool
    {
        return str_ends_with($type, 'DTO') || $this->isValueObjectClass($type);
    }
    
    private function extractClassNameFromType(string $type): string
    {
        // Remove namespace prefix if present
        if (str_contains($type, '\\')) {
            $parts = explode('\\', $type);
            return end($parts);
        }
        return $type;
    }
    
    private function resolveClassFullyQualifiedName(string $className): ?string
    {
        // If already fully qualified, return as-is
        if (str_contains($className, '\\') && class_exists($className)) {
            return $className;
        }
        
        // Common namespace patterns to try
        $namespaces = [
            'App\\DTO\\',
            'App\\DTO\\Request\\',
            'App\\DTO\\Response\\',
            'App\\DTO\\ValueObject\\',
        ];
        
        foreach ($namespaces as $namespace) {
            $fqn = $namespace . $className;
            if (class_exists($fqn)) {
                return $fqn;
            }
        }
        
        return null;
    }
    
    private function collapseType(string $type, array $visited = []): string|array
    {
        echo "collapseType called with: {$type}\n";
        
        // Check if it's a collapsible type
        if (!$this->isCollapsibleType($type)) {
            echo "Not collapsible, returning: {$type}\n";
            return $type;
        }
        echo "Type is collapsible\n";
        
        // Prevent infinite recursion
        if (in_array($type, $visited)) {
            echo "Already visited, returning: {$type}\n";
            return $type;
        }
        
        // Try to find and analyze the class
        $className = $this->extractClassNameFromType($type);
        echo "Class name extracted: {$className}\n";
        
        $fqn = $this->resolveClassFullyQualifiedName($className);
        echo "Resolved FQN: " . ($fqn ?: 'NULL') . "\n";
        
        if (!$fqn || !class_exists($fqn)) {
            echo "Class not found, returning original type: {$type}\n";
            return $type;
        }
        
        try {
            $reflection = new \ReflectionClass($fqn);
            echo "Reflection created successfully\n";
            
            // Handle abstract classes specially
            if ($reflection->isAbstract()) {
                echo "Class is abstract\n";
                if ($className === 'LayerProperties') {
                    $result = 'TextLayerProperties | ImageLayerProperties | ShapeLayerProperties';
                    echo "Returning union type for LayerProperties: {$result}\n";
                    return $result;
                }
                echo "Returning 'object' for other abstract classes\n";
                return 'object';
            }
            
            echo "Class is not abstract, would collapse properties\n";
            return $type; // Simplified for debugging
            
        } catch (\ReflectionException $e) {
            echo "Reflection exception: " . $e->getMessage() . "\n";
            return $type;
        }
    }
    
    public function testCollapseType() {
        $testTypes = [
            'App\\DTO\\ValueObject\\LayerProperties',
            'LayerProperties',
            'Transform',
            'string'
        ];
        
        foreach ($testTypes as $type) {
            echo "\n=== Testing type: {$type} ===\n";
            $result = $this->collapseType($type);
            echo "Result: " . (is_array($result) ? json_encode($result) : $result) . "\n";
        }
    }
}

(new MockCollapseType())->testCollapseType();
