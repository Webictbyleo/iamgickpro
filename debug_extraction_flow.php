<?php
require_once __DIR__ . '/backend/vendor/autoload.php';

// Simulate the extraction logic
function extractDtoPropertiesWithInheritance(\ReflectionClass $reflection): array
{
    $properties = [];
    $processedProperties = [];
    
    // Start with current class and walk up the inheritance chain
    $currentClass = $reflection;
    while ($currentClass !== false) {
        echo "Processing class: " . $currentClass->getName() . "\n";
        
        // Get constructor parameters (for property promotion)
        $constructor = $currentClass->getConstructor();
        if ($constructor) {
            echo "  Found constructor with " . count($constructor->getParameters()) . " parameters\n";
            foreach ($constructor->getParameters() as $param) {
                $paramName = $param->getName();
                echo "    Parameter: $paramName\n";
                
                // Skip if already processed (child class overrides parent)
                if (in_array($paramName, $processedProperties)) {
                    echo "      Skipped (already processed)\n";
                    continue;
                }
                
                // Check if it's a promoted property
                $isPromoted = false;
                try {
                    $property = $currentClass->getProperty($param->getName());
                    $isPromoted = $property->isPromoted();
                } catch (\ReflectionException $e) {
                    $isPromoted = false;
                }
                
                echo "      Is promoted: " . ($isPromoted ? 'yes' : 'no') . "\n";
                
                if ($isPromoted) {
                    $processedProperties[] = $paramName;
                    echo "      Added as promoted property\n";
                    $properties[] = ['name' => $paramName, 'source' => 'promoted'];
                } else {
                    echo "      Skipped (not promoted)\n";
                }
            }
        }
        
        // Get regular properties (non-promoted)
        echo "  Checking regular properties...\n";
        foreach ($currentClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $propName = $property->getName();
            echo "    Property: $propName\n";
            
            // Skip if already processed
            if (in_array($propName, $processedProperties)) {
                echo "      Skipped (already processed)\n";
                continue;
            }
            
            // Skip promoted properties (already handled above)
            if ($property->isPromoted()) {
                echo "      Skipped (promoted)\n";
                continue;
            }
            
            echo "      Added as regular property\n";
            $processedProperties[] = $propName;
            $properties[] = ['name' => $propName, 'source' => 'regular'];
        }
        
        // Move up the inheritance chain
        $currentClass = $currentClass->getParentClass();
        echo "  Parent class: " . ($currentClass ? $currentClass->getName() : 'none') . "\n";
    }
    
    return $properties;
}

$reflection = new \ReflectionClass('App\DTO\LoginRequestDTO');
$properties = extractDtoPropertiesWithInheritance($reflection);

echo "\nFinal properties:\n";
foreach ($properties as $prop) {
    echo "- {$prop['name']} ({$prop['source']})\n";
}
