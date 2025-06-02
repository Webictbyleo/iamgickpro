<?php

require_once 'vendor/autoload.php';

use App\DTO\ValueObject\DesignData;

// Test type extraction for DesignData arrays
$reflection = new ReflectionClass(DesignData::class);
$constructor = $reflection->getConstructor();

if ($constructor) {
    foreach ($constructor->getParameters() as $param) {
        if (in_array($param->getName(), ['animationSettings', 'gridSettings', 'viewSettings'])) {
            echo "Parameter: " . $param->getName() . "\n";
            
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType) {
                echo "  Reflection type: " . $type->getName() . "\n";
            }
            
            // Get the file content and examine PHPDoc manually
            $file = $constructor->getFileName();
            $content = file_get_contents($file);
            
            // Extract the section around this parameter
            $lines = explode("\n", $content);
            $paramPattern = '/\$' . $param->getName() . '/';
            
            foreach ($lines as $lineNum => $line) {
                if (preg_match($paramPattern, $line)) {
                    echo "  Found parameter at line " . ($lineNum + 1) . "\n";
                    echo "  Context (5 lines before to 2 lines after):\n";
                    
                    for ($i = max(0, $lineNum - 5); $i <= min(count($lines) - 1, $lineNum + 2); $i++) {
                        $marker = ($i === $lineNum) ? ' >>> ' : '     ';
                        echo "{$marker}{$lines[$i]}\n";
                    }
                    
                    // Look for @var annotation in preceding lines
                    for ($i = $lineNum - 1; $i >= max(0, $lineNum - 10); $i--) {
                        if (preg_match('/@var\s+([^\s\r\n]+)/', $lines[$i], $matches)) {
                            echo "  Found @var annotation: " . $matches[1] . "\n";
                            break;
                        }
                    }
                    
                    echo "\n";
                    break;
                }
            }
        }
    }
}
