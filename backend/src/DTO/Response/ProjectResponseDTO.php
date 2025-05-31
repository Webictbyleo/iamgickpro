<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Response DTO for Project data
 */
class ProjectResponseDTO extends BaseResponseDTO
{
    public function __construct(
        bool $success,
        string $message,
        public readonly ?array $project = null,
        public readonly ?array $projects = null,
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
        
        if ($this->project !== null) {
            $data['data']['project'] = $this->project;
        }
        
        if ($this->projects !== null) {
            $data['data']['projects'] = $this->projects;
            $data['data']['pagination'] = [
                'total' => $this->total,
                'page' => $this->page,
                'totalPages' => $this->totalPages,
            ];
        }
        
        return $data;
    }
}
