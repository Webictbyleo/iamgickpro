<?php

declare(strict_types=1);

namespace App\DTO\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a tag for categorizing and organizing content
 */
final readonly class Tag
{
    public function __construct(
        /**
         * Display name of the tag used for categorization and search
         * @var string $name
         */
        #[Assert\NotBlank(message: 'Tag name cannot be empty')]
        #[Assert\Length(
            min: 1,
            max: 50,
            minMessage: 'Tag must be at least 1 character long',
            maxMessage: 'Tag cannot exceed 50 characters'
        )]
        #[Assert\Regex(
            pattern: '/^[a-zA-Z0-9\s\-_]+$/',
            message: 'Tag can only contain letters, numbers, spaces, hyphens and underscores'
        )]
        public string $name
    ) {}

    public function __toString(): string
    {
        return $this->name;
    }
}
