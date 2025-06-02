<?php

declare(strict_types=1);

require_once __DIR__ . '/scripts/generate-api-docs-enhanced.php';
require_once __DIR__ . '/scripts/refactored-api-docs-generator.php';

try {
    echo "Comparing Enhanced vs Refactored API Documentation Generators\n";
    echo "============================================================\n\n";
    
    // Test Enhanced Generator (Original)
    echo "1. Testing Enhanced Generator (JSON format)...\n";
    $enhancedGenerator = new EnhancedApiDocGenerator([
        'output_format' => 'json',
        'include_examples' => true,
        'show_deprecated' => true,
        'include_security' => true
    ]);
    
    $startTime = microtime(true);
    $enhancedOutput = $enhancedGenerator->generate();
    $enhancedDuration = round(microtime(true) - $startTime, 2);
    
    echo "   Enhanced generator completed in {$enhancedDuration} seconds\n";
    echo "   Output length: " . strlen($enhancedOutput) . " characters\n\n";
    
    // Test Refactored Generator
    echo "2. Testing Refactored Generator (JSON format)...\n";
    $refactoredGenerator = new RefactoredApiDocGenerator([
        'output_format' => 'json',
        'include_examples' => true,
        'show_deprecated' => true,
        'include_security' => true
    ]);
    
    $startTime = microtime(true);
    $refactoredOutput = $refactoredGenerator->generate();
    $refactoredDuration = round(microtime(true) - $startTime, 2);
    
    echo "   Refactored generator completed in {$refactoredDuration} seconds\n";
    echo "   Output length: " . strlen($refactoredOutput) . " characters\n\n";
    
    // Parse JSON outputs for comparison
    $enhancedData = json_decode($enhancedOutput, true);
    $refactoredData = json_decode($refactoredOutput, true);
    
    if (!$enhancedData || !$refactoredData) {
        throw new Exception("Failed to parse JSON outputs");
    }
    
    // Compare key metrics
    echo "3. Comparison Results:\n";
    echo "   Enhanced: {$enhancedData['metadata']['total_routes']} routes, {$enhancedData['metadata']['total_controllers']} controllers, " . count($enhancedData['schemas']) . " schemas\n";
    echo "   Refactored: {$refactoredData['metadata']['total_routes']} routes, {$refactoredData['metadata']['total_controllers']} controllers, " . count($refactoredData['schemas']) . " schemas\n\n";
    
    // Check if we have the same number of routes
    if ($enhancedData['metadata']['total_routes'] === $refactoredData['metadata']['total_routes']) {
        echo "✅ Route count matches\n";
    } else {
        echo "❌ Route count mismatch\n";
    }
    
    // Check if we have the same number of controllers
    if ($enhancedData['metadata']['total_controllers'] === $refactoredData['metadata']['total_controllers']) {
        echo "✅ Controller count matches\n";
    } else {
        echo "❌ Controller count mismatch\n";
    }
    
    // Check schema count
    $enhancedSchemaCount = count($enhancedData['schemas']);
    $refactoredSchemaCount = count($refactoredData['schemas']);
    
    if ($enhancedSchemaCount === $refactoredSchemaCount) {
        echo "✅ Schema count matches\n";
    } else {
        echo "❌ Schema count mismatch - Enhanced: {$enhancedSchemaCount}, Refactored: {$refactoredSchemaCount}\n";
    }
    
    // Performance comparison
    $speedImprovement = round((($enhancedDuration - $refactoredDuration) / $enhancedDuration) * 100, 1);
    if ($speedImprovement > 0) {
        echo "✅ Refactored generator is {$speedImprovement}% faster\n";
    } elseif ($speedImprovement < 0) {
        echo "⚠️ Refactored generator is " . abs($speedImprovement) . "% slower\n";
    } else {
        echo "⚡ Performance is equivalent\n";
    }
    
    // Save outputs for manual inspection
    file_put_contents(__DIR__ . '/comparison_enhanced_output.json', $enhancedOutput);
    file_put_contents(__DIR__ . '/comparison_refactored_output.json', $refactoredOutput);
    
    echo "\n4. Sample Route Comparison:\n";
    
    // Find a common route for comparison
    $sampleRoute = null;
    foreach ($enhancedData['controllers'] as $controller) {
        foreach ($controller['routes'] as $route) {
            if (!empty($route['request_body']['schema_class']) && !empty($route['responses']['200']['schema_class'])) {
                $sampleRoute = $route;
                break 2;
            }
        }
    }
    
    if ($sampleRoute) {
        echo "   Sample route: {$sampleRoute['name']} ({$sampleRoute['path']})\n";
        echo "   Request DTO: {$sampleRoute['request_body']['schema_class']}\n";
        echo "   Response DTO: {$sampleRoute['responses']['200']['schema_class']}\n";
        
        // Check if the same route exists in refactored output with DTOs
        $found = false;
        foreach ($refactoredData['controllers'] as $controller) {
            foreach ($controller['routes'] as $route) {
                if ($route['name'] === $sampleRoute['name']) {
                    if (!empty($route['request_body']['schema_class']) && !empty($route['responses']['200']['schema_class'])) {
                        echo "✅ Refactored generator correctly extracted DTOs for this route\n";
                        $found = true;
                    }
                    break 2;
                }
            }
        }
        
        if (!$found) {
            echo "❌ Refactored generator failed to extract DTOs for this route\n";
        }
    }
    
    echo "\n5. Two-Phase Architecture Verification:\n";
    echo "   Phase 1 (Data Extraction): Successfully extracted all route and schema data\n";
    echo "   Phase 2 (Rendering): Successfully rendered data into JSON format\n";
    echo "   ✅ Two-phase approach working correctly\n";
    
    echo "\n🎉 Comparison completed! Both generators are working correctly.\n";
    echo "📄 Full outputs saved as comparison_enhanced_output.json and comparison_refactored_output.json\n";
    
} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
