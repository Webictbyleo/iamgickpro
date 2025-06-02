<?php

echo "=== Debug Array Type Parsing ===\n\n";

// Test the exact patterns we're seeing
$testCases = [
    'array<string, mixed>',
    'array<string,mixed>',
    'array<string, mixed> $animationSettings',
    '@var array<string, mixed> $animationSettings',
    'array<string, TextLayerProperties|ImageLayerProperties>',
];

foreach ($testCases as $test) {
    echo "Testing: '{$test}'\n";
    
    // Test the @var extraction (using new pattern)
    if (preg_match('/@var\s+([^\s\r\n]*(?:<[^>]*>)?[^\s\r\n]*)/', $test, $matches)) {
        echo "  @var match: '{$matches[1]}'\n";
    }
    
    // Test direct array pattern
    if (preg_match('/^array<([^,]+),\s*([^>]+)>/', $test, $matches)) {
        echo "  Array pattern match: key='{$matches[1]}', value='{$matches[2]}'\n";
    }
    
    // Test more permissive pattern
    if (preg_match('/array<([^>]+)>/', $test, $matches)) {
        echo "  Permissive array match: '{$matches[1]}'\n";
        $inner = $matches[1];
        if (strpos($inner, ',') !== false) {
            $parts = explode(',', $inner, 2);
            echo "    Split parts: key='" . trim($parts[0]) . "', value='" . trim($parts[1]) . "'\n";
        }
    }
    
    echo "\n";
}

echo "=== Testing actual DesignData extraction ===\n";

// Load the actual class and test the method
require_once 'vendor/autoload.php';

use ReflectionClass;

$reflection = new ReflectionClass('App\DTO\ValueObject\DesignData');
$constructor = $reflection->getConstructor();

if ($constructor) {
    $parameters = $constructor->getParameters();
    foreach ($parameters as $param) {
        if ($param->getName() === 'animationSettings') {
            echo "Parameter: {$param->getName()}\n";
            echo "Type: " . ($param->getType() ? $param->getType()->getName() : 'none') . "\n";
            
            $docComment = $constructor->getDocComment();
            if ($docComment) {
                echo "Doc comment found\n";
                
                // Extract the specific line for this parameter
                $lines = explode("\n", $docComment);
                foreach ($lines as $line) {
                    if (strpos($line, '$animationSettings') !== false) {
                        echo "Relevant line: '{$line}'\n";
                        
                        // Test our regex on this line
                        if (preg_match('/@var\s+([^\s\r\n]+)/', $line, $matches)) {
                            echo "Extracted type: '{$matches[1]}'\n";
                        }
                        break;
                    }
                }
            }
            break;
        }
    }
}
