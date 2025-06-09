<?php

declare(strict_types=1);

namespace App\Service\StockMedia;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use App\Service\StockMedia\StockMediaException;

/**
 * Service for robust JSON response handling in stock media integrations.
 *
 * Provides comprehensive validation, sanitization, and error handling for external API responses.
 * Addresses edge cases like malformed JSON, missing required fields, and XSS prevention.
 */
class StockMediaResponseValidator
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Safely extract and validate nested field values
     */
    public function extractField(array $data, string $field, mixed $default = null, ?string $expectedType = null): mixed
    {
        $value = $this->getNestedValue($data, $field, $default);
        
        // Type validation if specified
        if ($expectedType !== null && $value !== $default) {
            $value = $this->coerceType($value, $expectedType, $field);
        }
        
        // Sanitize string values to prevent XSS
        if (is_string($value) && $value !== '') {
            $value = $this->sanitizeString($value);
        }
        
        return $value;
    }

    /**
     * Extract field with specific string return type
     */
    public function extractStringField(array $data, string $field, ?string $default = null): ?string
    {
        $value = $this->extractField($data, $field, $default, 'string');
        return is_string($value) ? $value : $default;
    }

    /**
     * Extract field with specific integer return type
     */
    public function extractIntField(array $data, string $field, ?int $default = null): ?int
    {
        $value = $this->extractField($data, $field, $default, 'integer');
        return is_int($value) ? $value : $default;
    }

    /**
     * Extract field with specific boolean return type
     */
    public function extractBoolField(array $data, string $field, ?bool $default = null): ?bool
    {
        $value = $this->extractField($data, $field, $default, 'boolean');
        return is_bool($value) ? $value : $default;
    }

    /**
     * Extract field with specific array return type
     */
    public function extractArrayField(array $data, string $field, ?array $default = null): ?array
    {
        $value = $this->extractField($data, $field, $default, 'array');
        return is_array($value) ? $value : $default;
    }

    /**
     * Safely parse and validate JSON response from external APIs
     */
    public function parseAndValidateResponse(
        ResponseInterface $response, 
        array $requiredFields = [],
        string $provider = 'unknown'
    ): ?array {
        try {
            // First check if the response has content
            $content = $response->getContent();
            
            if (empty($content)) {
                $this->logger->warning('Empty response content received', ['provider' => $provider]);
                return null;
            }

            // Safely parse JSON with comprehensive error handling
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Malformed JSON response', [
                    'provider' => $provider,
                    'json_error' => json_last_error_msg(),
                    'content_preview' => substr($content, 0, 200)
                ]);
                throw new StockMediaException(
                    "Invalid JSON response from {$provider}: " . json_last_error_msg(),
                    500,
                    null,
                    $provider
                );
            }

            if (!is_array($data)) {
                $this->logger->error('Response is not a JSON object/array', [
                    'provider' => $provider,
                    'data_type' => gettype($data)
                ]);
                throw new StockMediaException(
                    "Unexpected response format from {$provider}",
                    500,
                    null,
                    $provider
                );
            }

            // Validate required fields exist
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!$this->hasNestedField($data, $field)) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                $this->logger->warning('Required fields missing from API response', [
                    'provider' => $provider,
                    'missing_fields' => $missingFields,
                    'available_fields' => array_keys($data)
                ]);
                
                // Return null when required fields are missing
                return null;
            }

            return $data;

        } catch (StockMediaException $e) {
            // Re-throw our custom exceptions
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error parsing API response', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            
            throw new StockMediaException(
                "Failed to parse response from {$provider}: " . $e->getMessage(),
                500,
                null,
                $provider
            );
        }
    }

    /**
     * Extract array items with validation for each item
     */
    public function extractItemsArray(array $data, string $field, array $requiredItemFields = []): array
    {
        $items = $this->extractArrayField($data, $field, []);
        
        if (empty($items)) {
            return [];
        }

        $validItems = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                $this->logger->warning('Item is not an array', ['item' => $item]);
                continue;
            }

            // Validate required fields in each item
            $hasAllFields = true;
            foreach ($requiredItemFields as $requiredField) {
                if (!$this->hasNestedField($item, $requiredField)) {
                    $hasAllFields = false;
                    break;
                }
            }

            if ($hasAllFields || empty($requiredItemFields)) {
                $validItems[] = $item;
            }
        }

        return $validItems;
    }

    /**
     * Sanitize string content to prevent XSS attacks
     */
    public function sanitizeString(string $value): string
    {
        // Remove script tags and dangerous attributes
        $value = strip_tags($value, '<strong><em><b><i><u><br><p>');
        
        // Escape HTML entities
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove any remaining javascript: or data: protocols
        $value = preg_replace('/javascript\s*:/i', '', $value);
        $value = preg_replace('/data\s*:/i', '', $value);
        
        return trim($value);
    }

    /**
     * Validate URL format and security
     */
    public function validateUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        // Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        // Only allow HTTP and HTTPS protocols
        if (!preg_match('/^https?:\/\//', $url)) {
            return null;
        }

        return $url;
    }

    /**
     * Get nested field value safely using dot notation
     */
    private function getNestedValue(array $data, string $field, mixed $default = null): mixed
    {
        if (!str_contains($field, '.')) {
            return $data[$field] ?? $default;
        }

        $keys = explode('.', $field);
        $value = $data;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return $default;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Check if nested field exists using dot notation
     */
    private function hasNestedField(array $data, string $field): bool
    {
        if (!str_contains($field, '.')) {
            return array_key_exists($field, $data);
        }

        $keys = explode('.', $field);
        $current = $data;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return false;
            }
            $current = $current[$key];
        }

        return true;
    }

    /**
     * Coerce value to expected type
     */
    private function coerceType(mixed $value, string $expectedType, string $field): mixed
    {
        return match ($expectedType) {
            'string' => is_string($value) ? $value : (string)$value,
            'integer', 'int' => is_int($value) ? $value : (int)$value,
            'boolean', 'bool' => is_bool($value) ? $value : (bool)$value,
            'array' => is_array($value) ? $value : [$value],
            'float', 'double' => is_float($value) ? $value : (float)$value,
            'mixed' => $value,
            default => $value
        };
    }
}
