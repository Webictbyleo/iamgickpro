<?php

echo "🔍 Simple Layer Controller Debug\n";
echo "=================================\n\n";

// Load JWT token
$tokenFile = 'jwt_token.txt';
if (!file_exists($tokenFile)) {
    echo "❌ JWT token file not found. Run generate_test_token.php first.\n";
    exit(1);
}

$jwtToken = trim(file_get_contents($tokenFile));
echo "🔐 JWT Token loaded: ✅\n\n";

$baseUrl = 'http://localhost:8000';

function makeRequest($method, $url, $data = null, $token = null) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            $token ? "Authorization: Bearer {$token}" : ''
        ],
        CURLOPT_POSTFIELDS => $data ? json_encode($data) : null,
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => 0, 'body' => ''];
    }
    
    return [
        'http_code' => $httpCode,
        'body' => $response,
        'data' => json_decode($response, true)
    ];
}

// Step 1: Create test project
echo "📝 Step 1: Creating test project\n";
echo "--------------------------------\n";

$createProjectData = [
    'name' => 'Debug Layer Test Project',
    'description' => 'Project for debugging layer issues',
    'isPublic' => false,
    'settings' => []
];

$response = makeRequest('POST', "{$baseUrl}/api/projects", $createProjectData, $jwtToken);

if ($response['http_code'] !== 201) {
    echo "❌ Failed to create project\n";
    echo "Status: {$response['http_code']}\n";
    echo "Response: {$response['body']}\n";
    exit(1);
}

echo "Project response: " . $response['body'] . "\n";
$projectId = $response['data']['data']['project']['id'];
echo "✅ Project created: ID {$projectId}\n\n";

// Step 2: Create design
echo "📝 Step 2: Creating test design\n";
echo "-------------------------------\n";

$createDesignData = [
    'project_id' => $projectId,
    'name' => 'Debug Layer Test Design',
    'width' => 800,
    'height' => 600
];

$response = makeRequest('POST', "{$baseUrl}/api/designs", $createDesignData, $jwtToken);

if ($response['http_code'] !== 201) {
    echo "❌ Failed to create design\n";
    echo "Status: {$response['http_code']}\n";
    echo "Response: {$response['body']}\n";
    exit(1);
}

echo "Design response: " . $response['body'] . "\n";
$designUuid = $response['data']['data']['design']['uuid'];
echo "✅ Design created: UUID {$designUuid}\n\n";

// Step 3: Create layer
echo "📝 Step 3: Creating test layer\n";
echo "------------------------------\n";

$createLayerData = [
    'designId' => $designUuid,
    'type' => 'text',
    'name' => 'Debug Test Layer',
    'transform' => [
        'x' => 100,
        'y' => 100,
        'width' => 200,
        'height' => 50
    ],
    'properties' => [
        'text' => 'Debug Layer Text',
        'fontSize' => 16,
        'color' => '#000000'
    ]
];

$response = makeRequest('POST', "{$baseUrl}/api/layers", $createLayerData, $jwtToken);

if ($response['http_code'] !== 201) {
    echo "❌ Failed to create layer\n";
    echo "Status: {$response['http_code']}\n";
    echo "Response: {$response['body']}\n";
    exit(1);
}

$layerId = $response['data']['data']['id'];
echo "✅ Layer created: ID {$layerId}\n\n";

// Step 4: Test layer duplication (this is failing)
echo "📝 Step 4: Testing layer duplication\n";
echo "------------------------------------\n";

$duplicateData = [
    'name' => 'Duplicated Debug Layer',
    'offsetX' => 20,
    'offsetY' => 20
];

$response = makeRequest('POST', "{$baseUrl}/api/layers/{$layerId}/duplicate", $duplicateData, $jwtToken);

echo "Duplicate Response Status: {$response['http_code']}\n";
echo "Duplicate Response Body: {$response['body']}\n";

if ($response['http_code'] === 201) {
    echo "✅ Layer duplication successful!\n";
} else {
    echo "❌ Layer duplication failed\n";
    if (isset($response['data']['errors'])) {
        foreach ($response['data']['errors'] as $error) {
            echo "   Error: {$error}\n";
        }
    }
}

echo "\n";

// Step 5: Test bulk update (this is failing)
echo "📝 Step 5: Testing bulk update\n";
echo "------------------------------\n";

$bulkUpdateData = [
    'layers' => [
        [
            'id' => $layerId,
            'updates' => [
                'name' => 'Bulk Updated Layer',
                'opacity' => 0.8
            ]
        ]
    ]
];

$response = makeRequest('PUT', "{$baseUrl}/api/layers/bulk-update", $bulkUpdateData, $jwtToken);

echo "Bulk Update Response Status: {$response['http_code']}\n";
echo "Bulk Update Response Body: {$response['body']}\n";

if ($response['http_code'] === 200) {
    echo "✅ Bulk update successful!\n";
} else {
    echo "❌ Bulk update failed\n";
    if (isset($response['data']['errors'])) {
        foreach ($response['data']['errors'] as $error) {
            echo "   Error: {$error}\n";
        }
    }
}

echo "\n🎯 Debug testing completed!\n";
