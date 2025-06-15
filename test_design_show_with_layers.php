<?php

require_once 'backend/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test script to verify design show endpoint returns complete design data with layers
 */

// Test configuration
$baseUrl = 'http://localhost:8000';
$testCredentials = [
    'email' => 'johndoe@example.com',
    'password' => 'Vyhd7Y#PjTb7!TA'
];

function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data && ($method === 'POST' || $method === 'PUT')) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => $response,
        'data' => json_decode($response, true)
    ];
}

echo "=== Testing Design Show Endpoint with Layers ===\n\n";

// Step 1: Login to get token
echo "1. Logging in...\n";
$loginResponse = makeRequest(
    "$baseUrl/api/auth/login",
    'POST',
    $testCredentials,
    ['Content-Type: application/json']
);

if ($loginResponse['code'] !== 200) {
    echo "❌ Login failed: " . $loginResponse['body'] . "\n";
    exit(1);
}

$token = $loginResponse['data']['token'] ?? null;
if (!$token) {
    echo "❌ No token received in login response\n";
    exit(1);
}

echo "✅ Login successful\n\n";

$authHeaders = [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
];

// Step 2: Get user's designs to find a design ID
echo "2. Fetching user designs...\n";
$designsResponse = makeRequest("$baseUrl/api/designs", 'GET', null, $authHeaders);

if ($designsResponse['code'] !== 200) {
    echo "❌ Failed to fetch designs: " . $designsResponse['body'] . "\n";
    exit(1);
}

$designs = $designsResponse['data']['data'] ?? [];
if (empty($designs)) {
    echo "⚠️ No designs found, creating a test design...\n";
    
    // Create a test design
    $createDesignData = [
        'name' => 'Test Design with Layers',
        'description' => 'Test design for layer persistence testing',
        'width' => 800,
        'height' => 600,
        'data' => [
            'version' => '1.0',
            'layers' => [],
            'canvas' => [
                'width' => 800,
                'height' => 600,
                'backgroundColor' => '#ffffff'
            ]
        ]
    ];
    
    $createResponse = makeRequest(
        "$baseUrl/api/designs",
        'POST',
        $createDesignData,
        $authHeaders
    );
    
    if ($createResponse['code'] !== 201) {
        echo "❌ Failed to create test design: " . $createResponse['body'] . "\n";
        exit(1);
    }
    
    $designId = $createResponse['data']['data']['id'];
    echo "✅ Created test design with ID: $designId\n\n";
} else {
    $designId = $designs[0]['id'];
    echo "✅ Found existing design with ID: $designId\n\n";
}

// Step 3: Add a test layer to the design
echo "3. Adding a test layer...\n";
$layerData = [
    'designId' => $designId,
    'type' => 'text',
    'name' => 'Test Text Layer',
    'properties' => [
        'text' => 'Hello World!',
        'fontSize' => 24,
        'fontFamily' => 'Arial',
        'fill' => '#000000'
    ],
    'transform' => [
        'x' => 100,
        'y' => 100,
        'width' => 200,
        'height' => 50,
        'rotation' => 0,
        'scaleX' => 1,
        'scaleY' => 1
    ],
    'zIndex' => 1,
    'visible' => true,
    'locked' => false,
    'opacity' => 1
];

$layerResponse = makeRequest(
    "$baseUrl/api/layers",
    'POST',
    $layerData,
    $authHeaders
);

if ($layerResponse['code'] !== 201) {
    echo "❌ Failed to create layer: " . $layerResponse['body'] . "\n";
    // Continue anyway to test existing layers
} else {
    echo "✅ Layer created successfully\n\n";
}

// Step 4: Test the design show endpoint
echo "4. Testing design show endpoint...\n";
$showResponse = makeRequest("$baseUrl/api/designs/$designId", 'GET', null, $authHeaders);

if ($showResponse['code'] !== 200) {
    echo "❌ Failed to fetch design: " . $showResponse['body'] . "\n";
    exit(1);
}

$designData = $showResponse['data']['data'] ?? [];
echo "✅ Design fetched successfully\n\n";

// Step 5: Verify the response structure
echo "5. Verifying response structure...\n";

// Check if layers are included in the response
if (isset($designData['layers'])) {
    echo "✅ Design response includes layers array\n";
    echo "   Found " . count($designData['layers']) . " layers\n";
    
    if (!empty($designData['layers'])) {
        $firstLayer = $designData['layers'][0];
        echo "   First layer details:\n";
        echo "   - ID: " . ($firstLayer['id'] ?? 'missing') . "\n";
        echo "   - UUID: " . ($firstLayer['uuid'] ?? 'missing') . "\n";
        echo "   - Name: " . ($firstLayer['name'] ?? 'missing') . "\n";
        echo "   - Type: " . ($firstLayer['type'] ?? 'missing') . "\n";
        echo "   - Visible: " . (($firstLayer['visible'] ?? false) ? 'true' : 'false') . "\n";
        echo "   - Z-Index: " . ($firstLayer['zIndex'] ?? 'missing') . "\n";
    }
} else {
    echo "❌ Design response does NOT include layers array\n";
}

// Check other expected fields
$expectedFields = ['id', 'uuid', 'title', 'data', 'width', 'height', 'createdAt', 'updatedAt'];
foreach ($expectedFields as $field) {
    if (isset($designData[$field])) {
        echo "✅ Field '$field' present\n";
    } else {
        echo "❌ Field '$field' missing\n";
    }
}

echo "\n=== Test Complete ===\n";

// Pretty print the full response for inspection
echo "\n6. Full design response:\n";
echo json_encode($designData, JSON_PRETTY_PRINT) . "\n";

?>
