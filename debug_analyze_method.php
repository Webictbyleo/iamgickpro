<?php

require_once __DIR__ . '/backend/vendor/autoload.php';

use App\DTO\LoginRequestDTO;

// Copy the actual analyzeRequestDTO method from our generator
function analyzeRequestDTO($className) {
    try {
        echo "Analyzing $className using reflection...\n";
        
        $reflection = new ReflectionClass($className);
        $properties = extractDtoPropertiesWithInheritance($className);
        
        echo "Properties extracted: " . count($properties) . "\n";
        
        if (empty($properties)) {
            echo "No properties found, falling back to legacy method...\n";
            return analyzeRequestDTOLegacy($className);
        }
        
        $result = [
            'properties' => []
        ];
        
        foreach ($properties as $property) {
            $propertyName = $property['name'];
            echo "Processing property: $propertyName\n";
            
            // Extract validation for this property using reflection
            $validation = extractValidationForPropertyReflection($className, $propertyName);
            echo "  Validation found: " . json_encode($validation) . "\n";
            
            $result['properties'][$propertyName] = [
                'type' => $property['type'],
                'nullable' => $property['nullable'],
                'hasDefault' => $property['hasDefault'],
                'description' => $property['description'],
                'validation' => $validation
            ];
        }
        
        echo "Final result properties count: " . count($result['properties']) . "\n";
        return $result;
        
    } catch (Exception $e) {
        echo "Exception caught: " . $e->getMessage() . "\n";
        echo "Falling back to legacy method...\n";
        return analyzeRequestDTOLegacy($className);
    }
}

function extractDtoPropertiesWithInheritance($className) {
    $properties = [];
    $processedProperties = [];
    
    $reflection = new ReflectionClass($className);
    
    // Check constructor parameters (promoted properties)
    $constructor = $reflection->getConstructor();
    if ($constructor) {
        foreach ($constructor->getParameters() as $param) {
            if ($param->isPromoted()) {
                $processedProperties[] = $param->getName();
                
                $properties[] = [
                    'name' => $param->getName(),
                    'type' => $param->getType() ? (string) $param->getType() : 'mixed',
                    'nullable' => $param->getType() ? $param->getType()->allowsNull() : false,
                    'hasDefault' => $param->isDefaultValueAvailable(),
                    'description' => ucfirst($param->getName()) . ' value',
                    'validation' => []
                ];
            }
        }
    }
    
    // Get all class properties (including inherited ones)
    $reflectionProperties = $reflection->getProperties();
    
    foreach ($reflectionProperties as $property) {
        $propertyName = $property->getName();
        
        if (!in_array($propertyName, $processedProperties)) {
            $properties[] = [
                'name' => $propertyName,
                'type' => $property->getType() ? (string) $property->getType() : 'mixed',
                'nullable' => $property->getType() ? $property->getType()->allowsNull() : false,
                'hasDefault' => false,
                'description' => ucfirst($propertyName) . ' value',
                'validation' => []
            ];
        }
    }
    
    return $properties;
}

function extractValidationForPropertyReflection($className, $propertyName) {
    $validation = [];
    
    try {
        $reflection = new ReflectionClass($className);
        
        // Try to get validation from property attributes
        if ($reflection->hasProperty($propertyName)) {
            $property = $reflection->getProperty($propertyName);
            $attributes = $property->getAttributes();
            
            foreach ($attributes as $attribute) {
                $attributeName = $attribute->getName();
                $args = $attribute->getArguments();
                
                if (str_contains($attributeName, 'Assert\\')) {
                    $constraintName = substr($attributeName, strrpos($attributeName, '\\') + 1);
                    
                    if ($constraintName === 'NotBlank') {
                        $validation['required'] = true;
                        if (isset($args['message'])) {
                            $validation['messages']['required'] = $args['message'];
                        }
                    } elseif ($constraintName === 'Email') {
                        $validation['email'] = true;
                        if (isset($args['message'])) {
                            $validation['messages']['email'] = $args['message'];
                        }
                    } elseif ($constraintName === 'Length') {
                        if (isset($args['min'])) {
                            $validation['minLength'] = $args['min'];
                        }
                        if (isset($args['max'])) {
                            $validation['maxLength'] = $args['max'];
                        }
                    }
                }
            }
        }
        
        // Also try constructor parameters (for promoted properties or constructor-based validation)
        $constructor = $reflection->getConstructor();
        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                if ($param->getName() === $propertyName) {
                    $attributes = $param->getAttributes();
                    
                    foreach ($attributes as $attribute) {
                        $attributeName = $attribute->getName();
                        $args = $attribute->getArguments();
                        
                        if (str_contains($attributeName, 'Assert\\')) {
                            $constraintName = substr($attributeName, strrpos($attributeName, '\\') + 1);
                            
                            if ($constraintName === 'NotBlank') {
                                $validation['required'] = true;
                                if (isset($args['message'])) {
                                    $validation['messages']['required'] = $args['message'];
                                }
                            }
                        }
                    }
                    break;
                }
            }
        }
        
    } catch (Exception $e) {
        // Ignore exceptions in validation extraction
    }
    
    return $validation;
}

function analyzeRequestDTOLegacy($className) {
    echo "Using legacy analysis for $className\n";
    return ['properties' => []];
}

// Test with LoginRequestDTO
echo "=== TESTING analyzeRequestDTO with LoginRequestDTO ===\n\n";
$result = analyzeRequestDTO(LoginRequestDTO::class);
echo "\nFINAL RESULT:\n";
print_r($result);
