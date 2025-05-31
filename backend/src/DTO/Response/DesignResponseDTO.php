<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\Design;

/**
 * Response DTO for Design data
 */
class DesignResponseDTO extends BaseResponseDTO
{
    public function __construct(
        bool $success,
        string $message,
        public readonly ?array $design = null,
        public readonly ?array $designs = null,
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
