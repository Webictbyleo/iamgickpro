<?php

// Test the regex pattern directly
$testDocComment = '* @var array<string, mixed> $animationSettings Global animation configuration';

echo "Testing regex pattern:\n";
echo "Input: {$testDocComment}\n";

if (preg_match('/@var\s+([^\s\r\n]+)/', $testDocComment, $matches)) {
    echo "Match found: " . $matches[1] . "\n";
} else {
    echo "No match found\n";
}

// Test what the current parsing gets
$fullDocComment = '/**
         * Animation settings for the design.
         * 
         * Contains configuration for design-level animations including:
         * - duration: Total animation duration in seconds
         * - loop: Whether animations should loop
         * - autoplay: Whether to start animations automatically
         * - easing: Global easing function for animations
         * 
         * @var array<string, mixed> $animationSettings Global animation configuration
         */';

echo "\nTesting full doc comment:\n";
if (preg_match('/@var\s+([^\s\r\n]+)/', $fullDocComment, $matches)) {
    echo "Match found: " . $matches[1] . "\n";
} else {
    echo "No match found\n";
}

// Test the array type conversion
$inputType = 'array<string, mixed>';
echo "\nTesting type conversion:\n";
echo "Input type: {$inputType}\n";

if (preg_match('/^array<([^,]+),\s*([^>]+)>$/', $inputType, $matches)) {
    echo "Array type match found:\n";
    echo "  Key type: " . trim($matches[1]) . "\n";
    echo "  Value type: " . trim($matches[2]) . "\n";
} else {
    echo "No array type match\n";
}
