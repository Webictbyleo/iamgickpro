<?php

// Quick test of the new TemplateSearchResponseDTO

require_once __DIR__ . '/vendor/autoload.php';

use App\DTO\Response\TemplateSearchResponseDTO;

// Test data
$templates = [
    [
        'id' => 1,
        'uuid' => 'template-uuid-1',
        'name' => 'Modern Business Card',
        'description' => 'A sleek business card template',
        'category' => 'business',
        'tags' => ['business', 'professional'],
        'thumbnailUrl' => 'https://example.com/thumb1.jpg',
        'previewUrl' => 'https://example.com/preview1.jpg',
        'width' => 1080,
        'height' => 720,
        'isPremium' => false,
        'isActive' => true,
        'rating' => 4.5,
        'ratingCount' => 10,
        'usageCount' => 25,
        'createdAt' => '2024-01-01T00:00:00+00:00',
        'updatedAt' => '2024-01-01T00:00:00+00:00',
    ],
    [
        'id' => 2,
        'uuid' => 'template-uuid-2',
        'name' => 'Creative Flyer',
        'description' => 'An eye-catching flyer template',
        'category' => 'marketing',
        'tags' => ['marketing', 'creative'],
        'thumbnailUrl' => 'https://example.com/thumb2.jpg',
        'previewUrl' => 'https://example.com/preview2.jpg',
        'width' => 1080,
        'height' => 1920,
        'isPremium' => true,
        'isActive' => true,
        'rating' => 4.8,
        'ratingCount' => 15,
        'usageCount' => 42,
        'createdAt' => '2024-01-02T00:00:00+00:00',
        'updatedAt' => '2024-01-02T00:00:00+00:00',
    ],
];

// Create the DTO
$dto = TemplateSearchResponseDTO::create(
    templates: $templates,
    page: 1,
    limit: 10,
    total: 2,
    message: 'Search completed successfully'
);

// Test the structure
$result = $dto->toArray();

echo "TemplateSearchResponseDTO Test:\n";
echo "==============================\n";
echo "Templates count: " . count($result['templates']) . "\n";
echo "Pagination page: " . $result['pagination']['page'] . "\n";
echo "Pagination total: " . $result['pagination']['total'] . "\n";
echo "Message: " . $result['message'] . "\n";
echo "First template name: " . $result['templates'][0]['name'] . "\n";
echo "\nFull structure:\n";
print_r($result);
echo "\nTest completed successfully!\n";
