<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdatePluginRequestDTO
{
    public function __construct(
        #[Assert\Length(min: 2, max: 100, minMessage: 'Plugin name must be at least 2 characters long', maxMessage: 'Plugin name cannot exceed 100 characters')]
        public ?string $name = null,

        #[Assert\Length(min: 10, max: 1000, minMessage: 'Plugin description must be at least 10 characters long', maxMessage: 'Plugin description cannot exceed 1000 characters')]
        public ?string $description = null,

        #[Assert\Count(min: 1, max: 5, minMessage: 'At least one category is required', maxMessage: 'Cannot exceed 5 categories')]
        #[Assert\All([
            new Assert\NotBlank(message: 'Category cannot be empty'),
            new Assert\Length(max: 50, maxMessage: 'Category name cannot exceed 50 characters')
        ])]
        public ?array $categories = null,

        #[Assert\Regex(pattern: '/^\d+\.\d+\.\d+(-[a-zA-Z0-9-]+)?$/', message: 'Version must follow semantic versioning (e.g., 1.0.0)')]
        public ?string $version = null,

        #[Assert\All([
            new Assert\Choice(choices: ['editor', 'filesystem', 'network', 'clipboard', 'notifications'], message: 'Invalid permission type')
        ])]
        public ?array $permissions = null,

        #[Assert\Type(type: 'array', message: 'Manifest must be a valid array')]
        public ?array $manifest = null,

        #[Assert\Choice(choices: ['pending', 'approved', 'rejected'], message: 'Invalid status. Must be pending, approved, or rejected')]
        public ?string $status = null,
    ) {}
}
