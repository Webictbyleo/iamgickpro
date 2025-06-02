<?php

declare(strict_types=1);

require_once __DIR__ . '/scripts/refactored-api-docs-generator.php';

try {
    echo "Testing Refactored API Documentation Generator\n";
    echo "=============================================\n\n";
    
    // Test with different output formats
    $formats = ['json', 'markdown'];
    
    foreach ($formats as $format) {
        echo "Testing {$format} format...\n";
        
        $generator = new RefactoredApiDocGenerator([
            'output_format' => $format,
            'include_examples' => true,
            'show_deprecated' => true,
            'include_security' => true
        ]);
        
        $startTime = microtime(true);
        $documentation = $generator->generate();
        $duration = round(microtime(true) - $startTime, 2);
        
        echo "Generated in {$duration} seconds\n";
        echo "Output length: " . strlen($documentation) . " characters\n";
        
        // Save to file
        $filename = __DIR__ . "/test_refactored_{$format}_output." . ($format === 'json' ? 'json' : 'md');
        file_put_contents($filename, $documentation);
        echo "Saved to: {$filename}\n";
        
        // Show first 500 characters as preview
        echo "Preview:\n";
        echo str_repeat('-', 50) . "\n";
        echo substr($documentation, 0, 500) . "\n";
        if (strlen($documentation) > 500) {
            echo "... (truncated)\n";
        }
        echo str_repeat('-', 50) . "\n\n";
    }
    
    echo "✅ Refactored generator test completed successfully!\n";
    
} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
