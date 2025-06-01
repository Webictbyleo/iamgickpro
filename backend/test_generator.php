<?php

echo "Starting Enhanced API Documentation Generator...\n";

require_once __DIR__ . '/vendor/autoload.php';

echo "Autoloader loaded successfully\n";

try {
    include __DIR__ . '/scripts/generate-api-docs-enhanced.php';
    echo "Enhanced generator included successfully\n";
} catch (Exception $e) {
    echo "Error loading enhanced generator: " . $e->getMessage() . "\n";
}

echo "Test completed\n";
