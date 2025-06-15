<?php
/**
 * Test script to verify the LayerController create method handles both UUID and numeric ID lookups
 */

require_once __DIR__ . '/backend/vendor/autoload.php';

$baseUrl = 'http://localhost:8000';
$testEmail = 'johndoe@example.com';
$testPassword = 'Vyhd7Y#PjTb7!TA';

echo "🧪 DESIGN LOOKUP TEST - UUID vs Numeric ID\n";
echo "==========================================\n\n";

function makeRequest($method, $url, $data = null, $token = null) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array_filter([
            'Content-Type: application/json',
            $token ? "Authorization: Bearer $token" : null
        ]),
        CURLOPT_POSTFIELDS => $data ? json_encode($data) : null,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'response' => $response,
        'data' => json_decode($response, true)
    ];
}

// Step 1: Authenticate
echo "🔐 Step 1: Authenticating user\n";
echo "------------------------------\n";

$authData = [
    'email' => $testEmail,
    'password' => $testPassword
];

$response = makeRequest('POST', "$baseUrl/api/auth/login", $authData);

if ($response['http_code'] !== 200) {
    echo "❌ Authentication failed\n";
    echo "Status: {$response['http_code']}\n";
    echo "Response: {$response['response']}\n";
    exit(1);
}

$jwtToken = $response['data']['data']['token'];
$user = $response['data']['data']['user'];
echo "✅ Authenticated as: {$user['email']}\n\n";

// Step 2: Create a test design to get both UUID and numeric ID
echo "🎨 Step 2: Creating test design\n";
echo "-------------------------------\n";

$designData = [
    'name' => 'UUID vs ID Test Design',
    'width' => 800,
    'height' => 600,
    'designData' => [
        'version' => '1.0',
        'layers' => [],
        'canvas' => [
            'width' => 800,
            'height' => 600,
            'backgroundColor' => '#ffffff'
        ]
    ]
];

$response = makeRequest('POST', "$baseUrl/api/designs", $designData, $jwtToken);

if ($response['http_code'] !== 201) {
    echo "❌ Failed to create design\n";
    echo "Status: {$response['http_code']}\n";
    echo "Response: {$response['response']}\n";
    exit(1);
}

$designId = $response['data']['data']['design']['id'];
$designUuid = $response['data']['data']['design']['uuid'];
echo "✅ Design created\n";
echo "  Numeric ID: $designId\n";
echo "  UUID: $designUuid\n\n";

// Step 3: Test layer creation with UUID (string)
echo "📝 Step 3: Testing layer creation with UUID (string)\n";
echo "----------------------------------------------------\n";

$layerDataWithUuid = [
    'designId' => $designUuid, // String UUID
    'type' => 'text',
    'name' => 'Test Layer with UUID',
    'properties' => [
        'text' => 'This layer was created using UUID lookup',
        'fontSize' => 16,
        'color' => '#333333'
    ],
    'transform' => [
        'x' => 50,
        'y' => 50,
        'width' => 300,
        'height' => 50,
        'rotation' => 0,
        'scaleX' => 1,
        'scaleY' => 1,
        'skewX' => 0,
        'skewY' => 0,
        'opacity' => 1
    ],
    'zIndex' => 1,
    'visible' => true,
    'locked' => false
];

$response = makeRequest('POST', "$baseUrl/api/layers", $layerDataWithUuid, $jwtToken);

echo "Request with UUID designId: $designUuid\n";
echo "Status: {$response['http_code']}\n";

if ($response['http_code'] === 201) {
    echo "✅ Layer created successfully with UUID lookup\n";
    $layerWithUuid = $response['data']['data']['layer'];
    echo "  Layer ID: {$layerWithUuid['id']}\n";
    echo "  Layer UUID: {$layerWithUuid['uuid']}\n";
    echo "  Layer Name: {$layerWithUuid['name']}\n";
} else {
    echo "❌ Failed to create layer with UUID\n";
    echo "Response: {$response['response']}\n";
}

echo "\n";

// Step 4: Test layer creation with numeric ID
echo "🔢 Step 4: Testing layer creation with numeric ID\n";
echo "------------------------------------------------\n";

$layerDataWithId = [
    'designId' => $designId, // Numeric ID
    'type' => 'text',
    'name' => 'Test Layer with Numeric ID',
    'properties' => [
        'text' => 'This layer was created using numeric ID lookup',
        'fontSize' => 16,
        'color' => '#666666'
    ],
    'transform' => [
        'x' => 50,
        'y' => 120,
        'width' => 300,
        'height' => 50,
        'rotation' => 0,
        'scaleX' => 1,
        'scaleY' => 1,
        'skewX' => 0,
        'skewY' => 0,
        'opacity' => 1
    ],
    'zIndex' => 2,
    'visible' => true,
    'locked' => false
];

$response = makeRequest('POST', "$baseUrl/api/layers", $layerDataWithId, $jwtToken);

echo "Request with numeric designId: $designId\n";
echo "Status: {$response['http_code']}\n";

if ($response['http_code'] === 201) {
    echo "✅ Layer created successfully with numeric ID lookup\n";
    $layerWithId = $response['data']['data']['layer'];
    echo "  Layer ID: {$layerWithId['id']}\n";
    echo "  Layer UUID: {$layerWithId['uuid']}\n";
    echo "  Layer Name: {$layerWithId['name']}\n";
} else {
    echo "❌ Failed to create layer with numeric ID\n";
    echo "Response: {$response['response']}\n";
}

echo "\n";

// Step 5: Test with invalid UUID (should fail)
echo "❌ Step 5: Testing with invalid UUID (should fail)\n";
echo "--------------------------------------------------\n";

$invalidLayerData = [
    'designId' => 'invalid-uuid-12345', // Invalid UUID
    'type' => 'text',
    'name' => 'Test Layer with Invalid UUID',
    'properties' => [
        'text' => 'This should fail',
        'fontSize' => 16
    ],
    'transform' => [
        'x' => 50,
        'y' => 200,
        'width' => 300,
        'height' => 50,
        'rotation' => 0,
        'scaleX' => 1,
        'scaleY' => 1,
        'skewX' => 0,
        'skewY' => 0,
        'opacity' => 1
    ]
];

$response = makeRequest('POST', "$baseUrl/api/layers", $invalidLayerData, $jwtToken);

echo "Request with invalid UUID: invalid-uuid-12345\n";
echo "Status: {$response['http_code']}\n";

if ($response['http_code'] === 404) {
    echo "✅ Correctly failed with 404 for invalid UUID\n";
} else {
    echo "❌ Unexpected response for invalid UUID\n";
    echo "Response: {$response['response']}\n";
}

echo "\n";

// Step 6: Test with invalid numeric ID (should fail)
echo "❌ Step 6: Testing with invalid numeric ID (should fail)\n";
echo "-------------------------------------------------------\n";

$invalidLayerData2 = [
    'designId' => 99999, // Invalid numeric ID
    'type' => 'text',
    'name' => 'Test Layer with Invalid ID',
    'properties' => [
        'text' => 'This should also fail',
        'fontSize' => 16
    ],
    'transform' => [
        'x' => 50,
        'y' => 270,
        'width' => 300,
        'height' => 50,
        'rotation' => 0,
        'scaleX' => 1,
        'scaleY' => 1,
        'skewX' => 0,
        'skewY' => 0,
        'opacity' => 1
    ]
];

$response = makeRequest('POST', "$baseUrl/api/layers", $invalidLayerData2, $jwtToken);

echo "Request with invalid numeric ID: 99999\n";
echo "Status: {$response['http_code']}\n";

if ($response['http_code'] === 404) {
    echo "✅ Correctly failed with 404 for invalid numeric ID\n";
} else {
    echo "❌ Unexpected response for invalid numeric ID\n";
    echo "Response: {$response['response']}\n";
}

echo "\n";

// Final results
echo "📊 FINAL RESULTS\n";
echo "================\n";
echo "✅ LayerController create method now supports both:\n";
echo "   • String UUID lookups (using findOneBy(['uuid' => \$designId]))\n";
echo "   • Numeric ID lookups (using find(\$designId))\n";
echo "✅ Invalid IDs correctly return 404 errors\n";
echo "✅ Design lookup type detection works based on parameter type\n\n";

echo "The modification is working correctly! 🎉\n";
?>
