<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpFoundation\JsonResponse;

class TestDtoExtraction
{
    public function testExtractDtosFromController(): void
    {
        // Test reflection on AuthController login method
        $reflection = new ReflectionClass('App\Controller\AuthController');
        $loginMethod = $reflection->getMethod('login');
        
        echo "=== Testing DTO Extraction ===\n";
        echo "Method: {$reflection->getName()}::{$loginMethod->getName()}\n";
        
        // Test parameter extraction
        echo "\n--- Parameters ---\n";
        foreach ($loginMethod->getParameters() as $param) {
            $type = $param->getType();
            if ($type && $type instanceof ReflectionNamedType) {
                echo "Parameter: {$param->getName()}, Type: {$type->getName()}\n";
                echo "Is DTO: " . (str_contains($type->getName(), 'DTO') ? 'Yes' : 'No') . "\n";
            }
        }
        
        // Test return type extraction
        echo "\n--- Return Type ---\n";
        $returnType = $loginMethod->getReturnType();
        if ($returnType && $returnType instanceof ReflectionNamedType) {
            echo "Return Type: {$returnType->getName()}\n";
        }
        
        // Test PHPDoc extraction
        echo "\n--- PHPDoc ---\n";
        $docComment = $loginMethod->getDocComment();
        if ($docComment) {
            echo "Doc Comment found: Yes\n";
            // Extract @return annotation manually
            if (preg_match('/@return\s+JsonResponse<([^>]+)>/', $docComment, $matches)) {
                echo "Response DTO Pattern: {$matches[1]}\n";
                
                // Test union type handling
                if (str_contains($matches[1], '|')) {
                    $types = array_map('trim', explode('|', $matches[1]));
                    echo "Union Types:\n";
                    foreach ($types as $type) {
                        echo "  - {$type}\n";
                        echo "    Is Error DTO: " . (str_contains($type, 'Error') ? 'Yes' : 'No') . "\n";
                    }
                }
            }
        } else {
            echo "Doc Comment found: No\n";
        }
        
        echo "\n=== Test Complete ===\n";
    }
}

$test = new TestDtoExtraction();
$test->testExtractDtosFromController();
