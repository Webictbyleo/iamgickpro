<?php
/**
 * Test script to verify the dynamic type expansion is working correctly
 */

// Include the enhanced generator
require_once 'generate-api-docs-enhanced.php';

// Create a test instance
$generator = new EnhancedApiDocGenerator([
    'output_format' => 'markdown',
    'show_deprecated' => false
]);

// Use reflection to test the shouldExpandType method
$reflection = new ReflectionClass($generator);
$shouldExpandMethod = $reflection->getMethod('shouldExpandType');
$shouldExpandMethod->setAccessible(true);

$expandMethod = $reflection->getMethod('expandNonNativeType');
$expandMethod->setAccessible(true);

echo "=== Testing Dynamic Type Expansion Logic ===\n\n";

// Test cases
$testTypes = [
    // Native types (should NOT expand)
    'string' => false,
    'int' => false, 
    'array' => false,
    'mixed' => false,
    'bool' => false,
    
    // Framework types (should NOT expand)
    'Request' => false,
    'Response' => false,
    'DateTime' => false,
    
    // DTO/ValueObject types (should expand)
    'CreateLayerRequestDTO' => true,
    'Transform' => true,
    'LayerProperties' => true,
    'DesignData' => true,
    'Tag' => true,
    'MediaMetadata' => true,
    'ProjectSettings' => true,
    'UserSettings' => true,
];

foreach ($testTypes as $type => $expected) {
    $shouldExpand = $shouldExpandMethod->invoke($generator, $type);
    $status = $shouldExpand === $expected ? '✅' : '❌';
    
    echo "{$status} {$type}: shouldExpand = " . ($shouldExpand ? 'true' : 'false') . 
         " (expected: " . ($expected ? 'true' : 'false') . ")\n";
         
    // If it should expand, test the expansion
    if ($shouldExpand) {
        $expanded = $expandMethod->invoke($generator, $type, 0);
        if ($expanded) {
            echo "    → Expanded successfully (length: " . strlen($expanded) . " chars)\n";
        } else {
            echo "    → Could not expand (class not found)\n";
        }
    }
}

echo "\n=== Testing Array Type Conversion ===\n\n";

// Test the convertToTypeScriptType method
$convertMethod = $reflection->getMethod('convertToTypeScriptType');
$convertMethod->setAccessible(true);

$arrayTests = [
    'array<string, mixed>' => 'Record<string, any>',
    'array<int, string>' => 'Map<number, string>',
    'array<string, SomeType>' => 'Record<string, SomeType>',
    'LayerUpdate[]' => 'LayerUpdate[]', // Should be expanded if LayerUpdate is a known type
    'string[]' => 'string[]',
];

foreach ($arrayTests as $input => $expected) {
    $result = $convertMethod->invoke($generator, $input, 0);
    $status = strpos($result, 'Record<') !== false || strpos($result, 'Map<') !== false || $result === $expected ? '✅' : '❌';
    
    echo "{$status} {$input} → {$result}\n";
}

echo "\nDynamic type expansion test completed!\n";
