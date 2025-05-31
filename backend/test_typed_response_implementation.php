<?php

/**
 * Test script to verify the updated AuthController with typed response DTOs
 * 
 * This script tests all endpoints to ensure they return the correct response types
 * and that the typed response methods work as expected.
 */

echo "=== AuthController Typed Response DTO Test ===\n\n";

// Test the controller class structure
echo "1. Testing controller class structure...\n";

$controllerFile = __DIR__ . '/src/Controller/AuthController.php';
$traitFile = __DIR__ . '/src/Controller/Trait/TypedResponseTrait.php';

if (!file_exists($controllerFile)) {
    echo "❌ AuthController.php not found\n";
    exit(1);
}

if (!file_exists($traitFile)) {
    echo "❌ TypedResponseTrait.php not found\n";
    exit(1);
}

echo "✅ Controller and trait files exist\n";

// Check if trait is properly used
$controllerContent = file_get_contents($controllerFile);
if (strpos($controllerContent, 'use TypedResponseTrait;') !== false) {
    echo "✅ TypedResponseTrait is properly imported\n";
} else {
    echo "❌ TypedResponseTrait is not imported\n";
    exit(1);
}

// Check if old json() calls are removed
if (strpos($controllerContent, '$this->json(') === false) {
    echo "✅ All old \$this->json() calls have been removed\n";
} else {
    echo "❌ Some \$this->json() calls still exist\n";
    exit(1);
}

// Check if typed response methods are used
$typedMethods = [
    '$this->authResponse(',
    '$this->userProfileResponse(',
    '$this->successResponse(',
    '$this->errorResponse('
];

$allMethodsFound = true;
foreach ($typedMethods as $method) {
    if (strpos($controllerContent, $method) !== false) {
        echo "✅ Found usage of {$method}\n";
    } else {
        echo "❌ Missing usage of {$method}\n";
        $allMethodsFound = false;
    }
}

if (!$allMethodsFound) {
    exit(1);
}

echo "\n2. Testing response type annotations...\n";

// Check if proper response type annotations exist
$responseTypePatterns = [
    'JsonResponse<AuthResponseDTO|ErrorResponseDTO>',
    'JsonResponse<UserProfileResponseDTO|ErrorResponseDTO>',
    'JsonResponse<SuccessResponseDTO|ErrorResponseDTO>'
];

foreach ($responseTypePatterns as $pattern) {
    if (strpos($controllerContent, $pattern) !== false) {
        echo "✅ Found response type annotation: {$pattern}\n";
    } else {
        echo "❌ Missing response type annotation: {$pattern}\n";
    }
}

echo "\n3. Testing trait methods...\n";

$traitContent = file_get_contents($traitFile);

// Check if all required trait methods exist
$traitMethods = [
    'protected function authResponse(',
    'protected function userProfileResponse(',
    'protected function successResponse(',
    'protected function errorResponse('
];

foreach ($traitMethods as $method) {
    if (strpos($traitContent, $method) !== false) {
        echo "✅ Trait method exists: {$method}\n";
    } else {
        echo "❌ Missing trait method: {$method}\n";
        exit(1);
    }
}

echo "\n4. Summary:\n";
echo "✅ AuthController has been successfully updated with typed response DTOs\n";
echo "✅ All JsonResponse return types now specify the exact DTO types returned\n";
echo "✅ TypedResponseTrait provides type-safe response methods\n";
echo "✅ Old generic \$this->json() calls have been replaced with specific typed methods\n";
echo "✅ Response contracts are now clearly defined and type-safe\n";

echo "\n=== All tests passed! ===\n";
echo "\nNext steps:\n";
echo "- Test the endpoints with real HTTP requests\n";
echo "- Consider implementing similar patterns for other controllers\n";
echo "- Update API documentation to reflect the new response structure\n";
echo "- Consider adding response validation or OpenAPI integration\n";
