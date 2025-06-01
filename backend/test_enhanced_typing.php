<?php

// Test the enhanced type hints in TemplateSearchResponseDTO

require_once __DIR__ . '/vendor/autoload.php';

use App\DTO\Response\TemplateSearchResponseDTO;

// Test with properly typed template data
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
];

// Create the DTO
$dto = TemplateSearchResponseDTO::create(
    templates: $templates,
    page: 1,
    limit: 10,
    total: 1,
    message: 'Template search with enhanced typing test'
);

echo "Enhanced Type Hints Test:\n";
echo "========================\n";
echo "Templates count: " . count($dto->templates) . "\n";
echo "First template ID: " . $dto->templates[0]['id'] . "\n";
echo "First template name: " . $dto->templates[0]['name'] . "\n";
echo "First template rating: " . $dto->templates[0]['rating'] . "\n";
echo "Test completed successfully!\n";
