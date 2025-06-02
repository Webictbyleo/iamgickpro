<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables from .env file
use Symfony\Component\Dotenv\Dotenv;
$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/.env');

echo "Starting generator test...\n";

try {
    require_once __DIR__ . '/scripts/generate-api-docs-enhanced.php';
    echo "File loaded successfully\n";
    
    $generator = new EnhancedApiDocGenerator(['debug' => true]);
    echo "Generator instantiated successfully\n";
    
    $result = $generator->generate();
    echo "Generation result: " . ($result ? "Success" : "Failed") . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
