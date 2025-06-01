<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Base response DTO providing common fields for all API responses.
 * 
 * Standardizes the response format across all API endpoints with
 * consistent success indicators, messages, and timestamps. Extended
 * by specific response DTOs to add endpoint-specific data.
 */
abstract class BaseResponseDTO
{
    public function __construct(
        /**
         * Human-readable message describing the operation result.
         * 
         * Provides context about what happened during the request
         * processing. Can be used for user notifications or debugging.
         * Should be descriptive but concise.
         */
        public readonly string $message,
        
        /**
         * Indicates whether the operation completed successfully.
         * 
         * Boolean flag that allows clients to quickly determine
         * if the request was processed successfully without parsing
         * the entire response structure.
         */
        public readonly bool $success = true,
        
        /**
         * ISO 8601 timestamp of when the response was generated.
         * 
         * Optional timestamp that can be provided or will be
         * automatically generated when converting to array format.
         * Useful for debugging and client-side caching.
         */
        public readonly ?string $timestamp = null
    ) {}

    /**
     * Converts the response DTO to array format for JSON serialization.
     * 
     * Transforms the response object into an associative array suitable
     * for JSON encoding and API response output. Generates timestamp
     * automatically if not provided during construction.
     * 
     * @return array<string, mixed> Response data as associative array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'timestamp' => $this->timestamp ?? (new \DateTimeImmutable())->format('c'),
        ];
    }
}
