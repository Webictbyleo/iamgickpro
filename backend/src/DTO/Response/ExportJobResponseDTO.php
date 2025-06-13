<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Response DTO for Export Job data
 */
class ExportJobResponseDTO extends BaseResponseDTO
{
    public function __construct(
        string $message,
        bool $success = true,
        public readonly ?array $job = null,
        public readonly ?array $jobs = null,
        public readonly ?int $total = null,
        public readonly ?int $page = null,
        public readonly ?int $totalPages = null,
        ?\DateTimeImmutable $timestamp = null
    ) {
        parent::__construct($message, $success, $timestamp);
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        
        if ($this->job !== null) {
            $data['data']['job'] = $this->job;
        }
        
        if ($this->jobs !== null) {
            $data['data']['jobs'] = $this->jobs;
            $data['data']['pagination'] = [
                'total' => $this->total,
                'page' => $this->page,
                'totalPages' => $this->totalPages,
            ];
        }
        
        return $data;
    }
}
