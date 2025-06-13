<?php

// Simple test to verify MediaService's enhanced uploadFile method
require_once __DIR__ . '/backend/config/bootstrap.php';

use App\Service\MediaService;
use App\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

try {
    // Get the MediaService from container
    $mediaService = $container->get(MediaService::class);
    
    // Get a test user (using our standard test user)
    $userRepository = $entityManager->getRepository(User::class);
    $testUser = $userRepository->findOneBy(['email' => 'johndoe@example.com']);
    
    if (!$testUser) {
        echo "❌ Test user not found. Please ensure johndoe@example.com exists.\n";
        exit(1);
    }
    
    echo "✅ MediaService loaded successfully\n";
    echo "✅ Test user found: {$testUser->getEmail()}\n";
    
    // Test extracting properties from different file types
    $testFiles = [
        // Test image properties
        '/var/www/html/iamgickpro/public/test_image.jpg' => 'image/jpeg',
        // Add more test files if available 
    ];
    
    foreach ($testFiles as $testFile => $expectedMimeType) {
        if (file_exists($testFile)) {
            echo "\n📁 Testing file: " . basename($testFile) . "\n";
            
            // Mock an uploaded file for testing
            $originalName = basename($testFile);
            $uploadedFile = new UploadedFile(
                $testFile,
                $originalName,
                $expectedMimeType,
                null,
                true // test mode
            );
            
            try {
                $media = $mediaService->uploadFile($uploadedFile, $testUser, "Test alt text");
                
                echo "✅ File uploaded successfully\n";
                echo "   - Media ID: {$media->getId()}\n";
                echo "   - Name: {$media->getName()}\n";
                echo "   - Type: {$media->getType()}\n";
                echo "   - MIME Type: {$media->getMimeType()}\n";
                echo "   - Size: {$media->getSize()} bytes\n";
                echo "   - Dimensions: {$media->getWidth()}x{$media->getHeight()}\n";
                echo "   - Duration: " . ($media->getDuration() ?? 'N/A') . "\n";
                echo "   - Thumbnail URL: " . ($media->getThumbnailUrl() ?? 'None') . "\n";
                
                $metadata = $media->getMetadata();
                echo "   - Metadata keys: " . implode(', ', array_keys($metadata)) . "\n";
                
            } catch (\Exception $e) {
                echo "❌ Upload failed: {$e->getMessage()}\n";
            }
        } else {
            echo "⚠️  Test file not found: $testFile\n";
        }
    }
    
    echo "\n🎉 Enhanced MediaService test completed!\n";
    
} catch (\Exception $e) {
    echo "❌ Test failed: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
