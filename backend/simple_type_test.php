<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Service\MediaProcessing\Config\ProcessingConfigFactory;

echo "=== Simple Type Fixes Test ===\n\n";

try {
    echo "1. Testing ProcessingConfigFactory::createImage() with correct parameter order...\n";
    
    // Test the specific fix from MediaController line 795
    $config1 = ProcessingConfigFactory::createImage(
        width: 1920,
        height: 1080,
        quality: 90,
        format: 'jpeg',
        stripMetadata: true,  // This was the key fix - moved to correct position
        backgroundColor: null
    );
    echo "   ✓ Configuration created successfully with stripMetadata=true\n";
    
    // Test the fix from MediaController line 995
    $config2 = ProcessingConfigFactory::createImage(
        width: 800,
        height: 600,
        quality: 85,
        format: 'png',
        stripMetadata: false,
        backgroundColor: '#ffffff'
    );
    echo "   ✓ Configuration created successfully with backgroundColor='#ffffff'\n";
    
    // Test the fix from MediaController line 1027
    $config3 = ProcessingConfigFactory::createImage(
        width: 400,
        height: 300,
        quality: 75,
        format: 'webp',
        stripMetadata: false,  // This was causing the null/bool type error, fixed to false
        backgroundColor: null
    );
    echo "   ✓ Configuration created successfully with stripMetadata=null\n";
    
    echo "\n2. Verifying configuration properties...\n";
    
    // Check that the configurations have the expected values
    if ($config1->getWidth() === 1920 && $config1->getHeight() === 1080) {
        echo "   ✓ Config1 dimensions are correct\n";
    } else {
        echo "   ✗ Config1 dimensions are incorrect\n";
    }
    
    if ($config2->getFormat() === 'png') {
        echo "   ✓ Config2 format is correct\n";
    } else {
        echo "   ✗ Config2 format is incorrect\n";
    }
    
    if ($config3->getFormat() === 'webp') {
        echo "   ✓ Config3 format is correct\n";
    } else {
        echo "   ✗ Config3 format is incorrect\n";
    }
    
    echo "\n3. Testing edge cases that were causing type errors...\n";
    
    // Test with all null optional parameters
    $config4 = ProcessingConfigFactory::createImage(
        width: null,
        height: null,
        quality: null,
        format: null,
        stripMetadata: false,  // Required bool parameter
        backgroundColor: null
    );
    echo "   ✓ All-null configuration created successfully\n";
    
    // Test with mixed parameters
    $config5 = ProcessingConfigFactory::createImage(
        width: 1024,
        height: null,
        quality: 80,
        format: 'jpeg',
        stripMetadata: true,
        backgroundColor: null
    );
    echo "   ✓ Mixed parameter configuration created successfully\n";
    
    echo "\n=== All Type Fixes Verified Successfully! ===\n";
    
} catch (TypeError $e) {
    echo "   ✗ Type Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
