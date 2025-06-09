<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

// Get JWT token for testing
$httpClient = HttpClient::create();

try {
    $response = $httpClient->request('POST', 'http://localhost:8000/api/auth/login', [
        'json' => [
            'email' => 'johndoe@example.com',
            'password' => 'Vyhd7Y#PjTb7!TA'
        ],
        'headers' => [
            'Content-Type' => 'application/json'
        ]
    ]);

    $data = $response->toArray();
    
    if (isset($data['token'])) {
        file_put_contents('jwt_token.txt', $data['token']);
        echo "JWT token saved to jwt_token.txt\n";
        echo "Token: " . substr($data['token'], 0, 50) . "...\n";
    } else {
        echo "Login response:\n";
        print_r($data);
    }
    
} catch (Exception $e) {
    echo "Error getting JWT token: " . $e->getMessage() . "\n";
}
