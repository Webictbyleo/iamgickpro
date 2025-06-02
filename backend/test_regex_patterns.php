<?php

echo "=== Testing Different Regex Patterns ===\n\n";

$testString = '@var array<string, mixed> $animationSettings';

$patterns = [
    '/@var\s+([^\s\r\n]+)/' => 'Original pattern',
    '/@var\s+([^\s\r\n]*(?:<[^>]*>)?[^\s\r\n]*)/' => 'My attempted fix',
    '/@var\s+([^\s\r\n]*<[^>]*>[^\s\r\n]*|\S+)/' => 'Alternative 1',
    '/@var\s+((?:[^\s<\r\n]+(?:<[^>]*>)?)+)/' => 'Alternative 2',
    '/@var\s+(\S+(?:<[^>]+>\S*)?)/' => 'Alternative 3',
    '/@var\s+([^\s]+(?:<[^>]+>)?)/' => 'Alternative 4',
];

foreach ($patterns as $pattern => $description) {
    echo "Testing: {$description}\n";
    echo "Pattern: {$pattern}\n";
    
    if (preg_match($pattern, $testString, $matches)) {
        echo "  Match: '{$matches[1]}'\n";
    } else {
        echo "  No match\n";
    }
    echo "\n";
}

// Try a completely different approach
echo "=== Testing Balanced Bracket Approach ===\n";

function extractVarType($docComment) {
    // Look for @var followed by a type
    if (preg_match('/@var\s+([^\s]+)/', $docComment, $matches)) {
        $type = $matches[1];
        
        // If it contains <, try to find the matching >
        if (strpos($type, '<') !== false && strpos($type, '>') === false) {
            // We have an incomplete match, try to extend it
            $varPos = strpos($docComment, '@var');
            if ($varPos !== false) {
                $afterVar = substr($docComment, $varPos + 4);
                if (preg_match('/\s+([^>\s]*<[^>]*>[^\s]*)/', $afterVar, $extendedMatches)) {
                    return trim($extendedMatches[1]);
                }
            }
        }
        
        return $type;
    }
    
    return '';
}

$result = extractVarType($testString);
echo "Custom function result: '{$result}'\n";
