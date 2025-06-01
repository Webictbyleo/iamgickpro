<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for bulk media deletion requests.
 * 
 * Handles bulk deletion of multiple media files by their UUIDs.
 * Used by the media management system to allow users to delete
 * multiple media files in a single operation with validation
 * and permission checks.
 */
class BulkDeleteMediaRequestDTO
{
    public function __construct(
        /**
         * Array of media file UUIDs to delete.
         * 
         * Each UUID represents a media file that the user wants to delete.
         * The system will validate ownership and existence before deletion.
         * Maximum 100 items can be deleted in a single request to prevent
         * performance issues and timeouts.
         * 
         * @var string[] Array of valid UUIDs
         */
        #[Assert\NotBlank(message: 'UUIDs are required')]
        #[Assert\Type('array', message: 'UUIDs must be an array')]
        #[Assert\Count(min: 1, max: 100, minMessage: 'At least one UUID is required', maxMessage: 'Cannot delete more than 100 items at once')]
        #[Assert\All([
            new Assert\NotBlank(message: 'UUID cannot be empty'),
            new Assert\Uuid(message: 'Invalid UUID format')
        ])]
        public readonly array $uuids,
    ) {}
}
