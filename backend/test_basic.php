<?php

echo "PHP is working\n";

require_once __DIR__ . '/vendor/autoload.php';
echo "Autoload successful\n";

try {
    require_once __DIR__ . '/scripts/generate-api-docs-enhanced.php';
    echo "Enhanced generator file loaded\n";
} catch (Throwable $e) {
    echo "Error loading file: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
