<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Response DTO for Template data
 */
class TemplateResponseDTO extends BaseResponseDTO
{
    public function __construct(
        bool $success,
        string $message,
        public readonly ?array $template = null,
        public readonly ?array $templates = null,
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
        
        if ($this->template !== null) {
            $data['data']['template'] = $this->template;
        }
        
        if ($this->templates !== null) {
            $data['data']['templates'] = $this->templates;
            $data['data']['pagination'] = [
                'total' => $this->total,
                'page' => $this->page,
                'totalPages' => $this->totalPages,
            ];
        }
        
        return $data;
    }
}
