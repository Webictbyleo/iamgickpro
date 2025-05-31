<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Simple success response DTO for operations that don't return data
 */
class SuccessResponseDTO extends BaseResponseDTO
{
    public function __construct(
        string $message,
        bool $success = true,
        ?string $timestamp = null
    ) {
        parent::__construct($message, $success, $timestamp);
    }
}
