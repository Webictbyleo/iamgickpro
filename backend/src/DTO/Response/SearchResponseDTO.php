<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Response DTO for Search results
 */
class SearchResponseDTO extends BaseResponseDTO
{
    public function __construct(
        bool $success,
        string $message,
        public readonly ?array $results = null,
        public readonly ?string $query = null,
        public readonly ?int $total = null,
        public readonly ?int $page = null,
        public readonly ?int $totalPages = null,
        ?\DateTimeImmutable $timestamp = null
    ) {
        parent::__construct($success, $message, $timestamp);
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        
        if ($this->results !== null) {
            $data['data']['results'] = $this->results;
            $data['data']['query'] = $this->query;
            $data['data']['pagination'] = [
                'total' => $this->total,
                'page' => $this->page,
                'totalPages' => $this->totalPages,
            ];
        }
        
        return $data;
    }
}
