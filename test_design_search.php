<?php

require_once 'backend/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load('backend/.env');

echo "=== Testing Design Search Functionality ===\n";

// Test API endpoints
$baseUrl = 'http://localhost:8000';

// First, let's test if we can access the search endpoint
echo "\n1. Testing search endpoint accessibility...\n";

$testEndpoints = [
    '/api/search?q=test&type=templates',
    '/api/search?q=test&type=media', 
    '/api/search?q=test&type=designs',
    '/api/search?q=test&type=all'
];

foreach ($testEndpoints as $endpoint) {
    $url = $baseUrl . $endpoint;
    echo "Testing: $url\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n",
            'timeout' => 5
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "❌ Failed to connect to $endpoint\n";
    } else {
        $data = json_decode($response, true);
        if ($data) {
            echo "✅ Endpoint accessible, returned JSON response\n";
            if (isset($data['message'])) {
                echo "   Message: " . $data['message'] . "\n";
            }
        } else {
            echo "⚠️  Endpoint returned non-JSON response\n";
        }
    }
    echo "\n";
}

echo "=== Test Complete ===\n";
