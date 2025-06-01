<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/scripts/generate-api-docs-enhanced.php';

// Test 1: Check if config shows request examples enabled
echo "Test 1: Configuration Test\n";
echo "========================\n";

$configFile = __DIR__ . '/config/api-docs-config.json';
$config = json_decode(file_get_contents($configFile), true);

echo "show_request_examples setting: " . ($config['show_request_examples'] ? 'true' : 'false') . "\n";
echo "show_response_examples setting: " . ($config['show_response_examples'] ? 'true' : 'false') . "\n\n";

// Test 2: Test the generator with request examples
echo "Test 2: Generator Test\n";
echo "=====================\n";

try {
    $generator = new EnhancedApiDocGenerator($config);
    
    // Test the generateRequestExample method directly if we have a DTO
    $reflection = new ReflectionClass($generator);
    $method = $reflection->getMethod('generateRequestExample');
    $method->setAccessible(true);
    
    // Use a known DTO class if it exists
    $dtoClasses = [
        'App\\DTO\\LoginRequestDTO',
        'App\\DTO\\RegisterRequestDTO',
        'App\\DTO\\MediaSearchDTO'
    ];
    
    foreach ($dtoClasses as $className) {
        if (class_exists($className)) {
            echo "Testing request example generation for: $className\n";
            $example = $method->invoke($generator, $className);
            if (!empty($example)) {
                echo "✓ Generated example:\n";
                echo substr($example, 0, 200) . "...\n\n";
            } else {
                echo "✗ No example generated\n\n";
            }
            break;
        }
    }
    
    echo "✓ Generator initialized successfully\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nTest 3: Verify Documentation Contains Examples\n";
echo "==============================================\n";

$docFile = __DIR__ . '/../API_DOCUMENTATION.md';
if (file_exists($docFile)) {
    $content = file_get_contents($docFile);
    $requestExamples = substr_count($content, '**Example Request:**');
    $responseExamples = substr_count($content, '**Example Response:**');
    
    echo "Found $requestExamples request examples in documentation\n";
    echo "Found $responseExamples response examples in documentation\n";
    
    if ($requestExamples > 0 && $responseExamples > 0) {
        echo "✓ Examples are being generated correctly\n";
    } else {
        echo "✗ Examples not found in documentation\n";
    }
} else {
    echo "✗ Documentation file not found\n";
}

echo "\nAll tests completed!\n";
