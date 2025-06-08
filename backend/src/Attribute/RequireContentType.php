<?php

declare(strict_types=1);

namespace App\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
readonly class RequireContentType
{
    public function __construct(
        public string|array $contentType,
        public string $message = 'Invalid content type'
    ) {
    }

    public function getContentTypes(): array
    {
        return is_array($this->contentType) ? $this->contentType : [$this->contentType];
    }
}
