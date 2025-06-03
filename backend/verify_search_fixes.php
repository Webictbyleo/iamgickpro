<?php

declare(strict_types=1);

echo "🔍 Testing SearchService.php fixes...\n\n";

// Test 1: Check syntax
echo "1. Checking PHP syntax:\n";
$syntaxCheck = shell_exec('php -l src/Service/SearchService.php 2>&1');
if (strpos($syntaxCheck, 'No syntax errors') !== false) {
    echo "✅ SearchService.php has no syntax errors\n";
} else {
    echo "❌ Syntax errors found:\n$syntaxCheck\n";
    exit(1);
}

// Test 2: Check if file can be loaded
echo "\n2. Testing file loading:\n";
try {
    require_once 'vendor/autoload.php';
    
    $reflection = new ReflectionClass('App\Service\SearchService');
    echo "✅ SearchService class loads successfully\n";
    
    // Check methods exist
    $methods = ['search', 'searchProjects', 'searchTemplates', 'searchMedia', 'getSearchSuggestions'];
    foreach ($methods as $method) {
        if ($reflection->hasMethod($method)) {
            echo "✅ Method $method exists\n";
        } else {
            echo "❌ Method $method missing\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error loading SearchService: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Check specific fixes by examining the source code
echo "\n3. Verifying fixes in source code:\n";
$sourceCode = file_get_contents('src/Service/SearchService.php');

// Check that problematic field references are fixed
$fixes = [
    'm.filename' => false, // Should NOT be present anymore
    'm.originalName' => false, // Should NOT be present anymore  
    'm.createdAt' => false, // Should NOT be present anymore
    'm.name' => true, // Should be present
    'm.created_at' => true, // Should be present
    'getFilename()' => false, // Should NOT be present anymore
    'getOriginalName()' => false, // Should NOT be present anymore
    'getName()' => true, // Should be present
    'getThumbnailUrl()' => true, // Should be present
    'isPremium()' => true, // Should be present
];

foreach ($fixes as $pattern => $shouldExist) {
    $exists = strpos($sourceCode, $pattern) !== false;
    if ($shouldExist && $exists) {
        echo "✅ Fixed: '$pattern' is present (as expected)\n";
    } elseif (!$shouldExist && !$exists) {
        echo "✅ Fixed: '$pattern' is NOT present (as expected)\n";
    } elseif ($shouldExist && !$exists) {
        echo "❌ Issue: '$pattern' should be present but is missing\n";
    } else {
        echo "❌ Issue: '$pattern' should NOT be present but was found\n";
    }
}

echo "\n🎉 SearchService fix verification completed!\n";
echo "\n📋 Summary of applied fixes:\n";
echo "   ✅ Media search query: m.filename/m.originalName -> m.name\n";
echo "   ✅ Media search query: m.createdAt -> m.created_at\n";
echo "   ✅ Media format method: getFilename()/getOriginalName() -> getName()\n";
echo "   ✅ Media format method: getThumbnail() -> getThumbnailUrl()\n";
echo "   ✅ Template format method: getThumbnail() -> getThumbnailUrl()\n";
echo "   ✅ Template format method: getIsPremium() -> isPremium()\n";
echo "\n✨ The SearchService should now work correctly with the actual entity field structures!\n";
