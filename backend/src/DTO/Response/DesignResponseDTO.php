<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\Design;

/**
 * Response DTO for Design operations and data transfer.
 * 
 * This class handles API responses for design-related operations, supporting both
 * single design responses and paginated design collections. It extends BaseResponseDTO
 * to include standard response fields (success, message, timestamp) while adding
 * design-specific data fields.
 * 
 * The DTO serves dual purposes:
 * 1. Single design responses (create, show, update, duplicate operations)
 * 2. Paginated design collections (index, search operations)
 * 
 * Usage Examples:
 * 
 * Single design response:
 * ```php
 * $response = new DesignResponseDTO(
 *     success: true,
 *     message: 'Design retrieved successfully',
 *     design: $designArray
 * );
 * ```
 * 
 * Paginated designs response:
 * ```php
 * $response = new DesignResponseDTO(
 *     success: true,
 *     message: 'Designs retrieved successfully',
 *     designs: $designsArray,
 *     total: 50,
 *     page: 1,
 *     totalPages: 5
 * );
 * ```
 * 
 * @package App\DTO\Response
 * @author iamgickpro Development Team
 * @since 1.0.0
 * 
 * @see BaseResponseDTO For inherited base response fields
 * @see DesignController For usage in design operations
 * @see ResponseDTOFactory For standardized response creation
 */
class DesignResponseDTO extends BaseResponseDTO
{
    /**
     * Constructs a new DesignResponseDTO instance.
     * 
     * @param bool $success Whether the operation was successful
     * @param string $message Human-readable message describing the operation result
     * @param array<string, mixed>|null $design Single design data array (for individual design operations)
     *                          Contains serialized design entity with fields like:
     *                          - id: string (UUID)
     *                          - name: string
     *                          - designData: array (canvas data structure)
     *                          - thumbnail: string|null (URL or base64)
     *                          - createdAt: string (ISO 8601 datetime)
     *                          - updatedAt: string (ISO 8601 datetime)
     * @param array<int, array<string, mixed>>|null $designs Array of design data arrays (for paginated responses)
     *                           Each element has the same structure as $design parameter
     * @param int|null $total Total number of designs available (used with pagination)
     * @param int|null $page Current page number (1-based, used with pagination)
     * @param int|null $totalPages Total number of pages available (used with pagination)
     * @param \DateTimeImmutable|null $timestamp Response generation timestamp
     *                                          If null, current timestamp will be used
     * 
     * @throws \InvalidArgumentException When both design and designs are provided
     *                                  or when pagination fields are inconsistent
     */
    public function __construct(
        bool $success,
        string $message,
        /** @var array<string, mixed>|null */
        public readonly ?array $design = null,
        /** @var array<int, array<string, mixed>>|null */
        public readonly ?array $designs = null,
        public readonly ?int $total = null,
        public readonly ?int $page = null,
        public readonly ?int $totalPages = null,
        ?\DateTimeImmutable $timestamp = null
    ) {
        parent::__construct($success, $message, $timestamp);
    }

    /**
     * Converts the DTO to an associative array for JSON serialization.
     * 
     * Merges the base response data with design-specific fields. The output structure
     * varies based on whether this is a single design or paginated response:
     * 
     * Single design response structure:
     * ```json
     * {
     *   "success": true,
     *   "message": "Design retrieved successfully",
     *   "timestamp": "2025-06-02T10:30:00Z",
     *   "data": {
     *     "design": {
     *       "id": "uuid-string",
     *       "name": "My Design",
     *       "designData": {...},
     *       "thumbnail": "...",
     *       "createdAt": "2025-06-01T15:30:00Z",
     *       "updatedAt": "2025-06-02T10:30:00Z"
     *     }
     *   }
     * }
     * ```
     * 
     * Paginated designs response structure:
     * ```json
     * {
     *   "success": true,
     *   "message": "Designs retrieved successfully",
     *   "timestamp": "2025-06-02T10:30:00Z",
     *   "data": {
     *     "designs": [...],
     *     "pagination": {
     *       "total": 50,
     *       "page": 1,
     *       "totalPages": 5
     *     }
     *   }
     * }
     * ```
     * 
     * @return array<string, mixed> The DTO data as an associative array ready for JSON encoding
     * 
     * @see BaseResponseDTO::toArray() For inherited base structure
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        
        if ($this->design !== null) {
            $data['data']['design'] = $this->design;
        }
        
        if ($this->designs !== null) {
            $data['data']['designs'] = $this->designs;
            $data['data']['pagination'] = [
                'total' => $this->total,
                'page' => $this->page,
                'totalPages' => $this->totalPages,
            ];
        }
        
        return $data;
    }
}
