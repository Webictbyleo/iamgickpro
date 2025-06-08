<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Paginated response DTO for API endpoints
 * 
 * @template T
 */
readonly class PaginatedResponseDTO
{
    public function __construct(
        /** @var T[] */
        public array $data,
        public int $page,
        public int $limit,
        public int $total,
        public int $totalPages,
        public string $message = 'Success',
    ) {}

    public function toArray(): array
    {
        return [
            'data' => array_map(
                fn($item) => (is_object($item) && method_exists($item, 'toArray')) ? $item->toArray() : $item,
                $this->data
            ),
            'pagination' => [
                'page' => $this->page,
                'limit' => $this->limit,
                'total' => $this->total,
                'totalPages' => $this->totalPages,
            ],
            'message' => $this->message,
        ];
    }
}
