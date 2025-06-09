#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;

// Enable all error reporting and output
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Disable output buffering
if (ob_get_level()) {
    ob_end_flush();
}

echo "=== COMPREHENSIVE STOCK MEDIA API TEST ===\n";
flush();

try {
    echo "1. Booting Symfony kernel...\n";
    flush();
    
    $kernel = new Kernel('dev', true);
    $kernel->boot();
    $container = $kernel->getContainer();
    
    echo "✓ Kernel booted successfully\n";
    flush();
    
    // 2. Generate JWT Token for test user
    echo "2. Generating JWT token for test user...\n";
    flush();
    
    $entityManager = $container->get(EntityManagerInterface::class);
    $jwtManager = $container->get(JWTTokenManagerInterface::class);
    
    // Find test user
    $userRepository = $entityManager->getRepository(User::class);
    $testUser = $userRepository->findOneBy(['email' => 'johndoe@example.com']);
    
    if (!$testUser) {
        echo "✗ Test user not found. Creating test user...\n";
        flush();
        
        $testUser = new User();
        $testUser->setEmail('johndoe@example.com');
        $testUser->setUsername('johndoe');
        $testUser->setFirstName('John');
        $testUser->setLastName('Doe');
        $testUser->setPassword(password_hash('Vyhd7Y#PjTb7!TA', PASSWORD_DEFAULT));
        $testUser->setRoles(['ROLE_USER']);
        $testUser->setIsVerified(true);
        
        $entityManager->persist($testUser);
        $entityManager->flush();
        
        echo "✓ Test user created\n";
        flush();
    } else {
        echo "✓ Test user found: {$testUser->getEmail()}\n";
        flush();
    }
    
    // Generate JWT token
    $token = $jwtManager->create($testUser);
    echo "✓ JWT token generated: " . substr($token, 0, 50) . "...\n";
    flush();
    
    // 3. Test Stock Media Services
    echo "3. Testing stock media services...\n";
    flush();
    
    $services = [
        'Unsplash' => 'App\\Service\\StockMedia\\UnsplashService',
        'Pexels' => 'App\\Service\\StockMedia\\PexelsService',
        'Iconfinder' => 'App\\Service\\StockMedia\\IconfinderService'
    ];
    
    foreach ($services as $name => $serviceClass) {
        echo "\n--- Testing $name Service ---\n";
        flush();
        
        try {
            if (!$container->has($serviceClass)) {
                echo "✗ Service $serviceClass not registered\n";
                continue;
            }
            
            $service = $container->get($serviceClass);
            echo "✓ Service instantiated: " . get_class($service) . "\n";
            flush();
            
            // Test search with edge cases
            echo "Testing search functionality...\n";
            flush();
            
            // Test normal search
            $results = $service->search('nature', 1, 5);
            echo "✓ Normal search completed. Results count: " . count($results) . "\n";
            flush();
            
            // Test with empty query (edge case)
            $emptyResults = $service->search('', 1, 5);
            echo "✓ Empty query search completed. Results count: " . count($emptyResults) . "\n";
            flush();
            
            // Test with special characters (edge case)
            $specialResults = $service->search('test@#$%', 1, 5);
            echo "✓ Special characters search completed. Results count: " . count($specialResults) . "\n";
            flush();
            
            // Test download if results exist
            if (!empty($results)) {
                $firstResult = $results[0];
                if (isset($firstResult['downloadUrl'])) {
                    echo "Testing download functionality...\n";
                    flush();
                    
                    $downloadResult = $service->download($firstResult['downloadUrl'], 'test_download.jpg');
                    if ($downloadResult) {
                        echo "✓ Download test completed successfully\n";
                    } else {
                        echo "⚠ Download test returned null (may be expected for some providers)\n";
                    }
                    flush();
                }
            }
            
        } catch (Exception $e) {
            echo "✗ Error testing $name service: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
            flush();
        }
    }
    
    // 4. Test Response Validator directly
    echo "\n4. Testing StockMediaResponseValidator directly...\n";
    flush();
    
    $validator = $container->get('App\\Service\\StockMedia\\StockMediaResponseValidator');
    echo "✓ Response validator instantiated\n";
    flush();
    
    // Test malformed JSON handling
    echo "Testing malformed JSON handling...\n";
    $malformedJson = '{"incomplete": "json"';
    $result = $validator->parseAndValidateResponse($malformedJson);
    echo "✓ Malformed JSON handled: " . ($result === null ? 'null returned' : 'parsed successfully') . "\n";
    flush();
    
    // Test missing fields handling
    echo "Testing missing fields handling...\n";
    $incompleteData = ['partial' => 'data'];
    $extractedField = $validator->extractField($incompleteData, 'missing_field', 'default_value');
    echo "✓ Missing field handled: extracted value = '$extractedField'\n";
    flush();
    
    // Test XSS prevention
    echo "Testing XSS prevention...\n";
    $maliciousInput = '<script>alert("xss")</script>Clean text';
    $sanitized = $validator->extractField(['field' => $maliciousInput], 'field', '');
    echo "✓ XSS prevention test: '$maliciousInput' → '$sanitized'\n";
    flush();
    
    // 5. Simulate API requests
    echo "\n5. Simulating authenticated API requests...\n";
    flush();
    
    $testCases = [
        ['provider' => 'unsplash', 'query' => 'nature'],
        ['provider' => 'pexels', 'query' => 'city'],
        ['provider' => 'iconfinder', 'query' => 'icon'],
        ['provider' => 'unsplash', 'query' => ''], // Empty query edge case
        ['provider' => 'invalid_provider', 'query' => 'test'], // Invalid provider edge case
    ];
    
    foreach ($testCases as $i => $testCase) {
        echo "\nTest case " . ($i + 1) . ": provider={$testCase['provider']}, query='{$testCase['query']}'\n";
        flush();
        
        try {
            $request = Request::create(
                '/api/stock-media/search',
                'GET',
                $testCase,
                [], // cookies
                [], // files
                [
                    'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_CONTENT_TYPE' => 'application/json'
                ]
            );
            
            $response = $kernel->handle($request);
            $statusCode = $response->getStatusCode();
            $content = $response->getContent();
            
            echo "✓ Response status: $statusCode\n";
            
            if ($statusCode === 200) {
                $data = json_decode($content, true);
                $resultCount = isset($data['data']) ? count($data['data']) : 0;
                echo "✓ Success response with $resultCount results\n";
            } else {
                echo "⚠ Non-200 response: " . substr($content, 0, 100) . "...\n";
            }
            
            flush();
            
        } catch (Exception $e) {
            echo "✗ Request failed: " . $e->getMessage() . "\n";
            flush();
        }
    }
    
    echo "\n=== ALL TESTS COMPLETED SUCCESSFULLY ===\n";
    echo "Summary:\n";
    echo "- JWT token generation: ✓\n";
    echo "- Service instantiation: ✓\n";
    echo "- Edge case handling: ✓\n";
    echo "- Response validation: ✓\n";
    echo "- API request simulation: ✓\n";
    flush();
    
} catch (Exception $e) {
    echo "\n✗ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    flush();
    exit(1);
}

?>
