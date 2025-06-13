<?php

// Simple debug script to check template creation response parsing

function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $data ? json_encode($data) : null,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    return [
        'code' => $httpCode,
        'body' => $body,
        'headers' => $headers,
        'data' => json_decode($body, true)
    ];
}

$baseUrl = 'http://localhost:8000';
$tokenFile = __DIR__ . '/test_jwt_token.txt';

// Load JWT token
$jwt = null;
if (file_exists($tokenFile)) {
    $jwt = trim(file_get_contents($tokenFile));
}

$authHeaders = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $jwt
];

// Test template data
$validTemplate = [
    'name' => 'Debug Template ' . date('Y-m-d H:i:s'),
    'description' => 'A debug template for testing',
    'category' => 'social-media',
    'tags' => [
        ['name' => 'debug'],
        ['name' => 'test']
    ],
    'width' => 1080,
    'height' => 1080,
    'canvasSettings' => [
        'backgroundColor' => '#ffffff',
        'gridEnabled' => true
    ],
    'layers' => [
        [
            'type' => 'text',
            'content' => 'Debug Text',
            'position' => ['x' => 100, 'y' => 200]
        ]
    ],
    'thumbnailUrl' => 'https://example.com/thumb.jpg',
    'previewUrl' => 'https://example.com/preview.jpg',
    'isPremium' => false,
    'isActive' => true
];

echo "Making template creation request...\n";
$response = makeRequest("$baseUrl/api/templates", 'POST', $validTemplate, $authHeaders);

echo "HTTP Code: " . $response['code'] . "\n";
echo "Body length: " . strlen($response['body']) . "\n";
echo "Data exists: " . (isset($response['data']) ? 'YES' : 'NO') . "\n";

if (isset($response['data'])) {
    echo "Data type: " . gettype($response['data']) . "\n";
    if (is_array($response['data'])) {
        echo "Data keys: " . implode(', ', array_keys($response['data'])) . "\n";
        
        if (isset($response['data']['template'])) {
            echo "Template exists: YES\n";
            echo "Template ID: " . ($response['data']['template']['id'] ?? 'NOT FOUND') . "\n";
            echo "Template UUID: " . ($response['data']['template']['uuid'] ?? 'NOT FOUND') . "\n";
        } else {
            echo "Template exists: NO\n";
        }
    }
} else {
    echo "JSON decode error: " . json_last_error_msg() . "\n";
    echo "First 200 chars of body: " . substr($response['body'], 0, 200) . "\n";
}

echo "\nTest condition check:\n";
echo "Code === 201: " . ($response['code'] === 201 ? 'TRUE' : 'FALSE') . "\n";
echo "isset template id: " . (isset($response['data']['template']['id']) ? 'TRUE' : 'FALSE') . "\n";
echo "Combined condition: " . (($response['code'] === 201 && isset($response['data']['template']['id'])) ? 'TRUE' : 'FALSE') . "\n";
