<?php

declare(strict_types=1);

namespace App\Attribute;

use Attribute;

/**
 * Attribute to mark DTO properties that should be populated from query parameters
 * instead of request body for GET requests.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
readonly class QueryParam
{
    public function __construct(
        /**
         * The query parameter name. If not specified, uses the property name.
         */
        public ?string $name = null,
        
        /**
         * Whether this parameter is required in the query string.
         */
        public bool $required = false,
        
        /**
         * Default value to use if the parameter is not present.
         */
        public mixed $default = null,
    ) {}
}
