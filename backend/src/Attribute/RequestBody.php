<?php

declare(strict_types=1);

namespace App\Attribute;

use Attribute;

/**
 * Attribute to mark DTO properties that should be populated from request body
 * even when the request also has query parameters.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
readonly class RequestBody
{
    public function __construct(
        /**
         * The property name in the request body. If not specified, uses the property name.
         */
        public ?string $name = null,
        
        /**
         * Whether this property is required in the request body.
         */
        public bool $required = false,
    ) {}
}
