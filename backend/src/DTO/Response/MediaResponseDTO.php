<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Response DTO for Media data
 */
class MediaResponseDTO extends BaseResponseDTO
{
    public function __construct(
        bool $success,
        string $message,
        public readonly ?array $media = null,
        public readonly ?array $mediaList = null,
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
        
        if ($this->media !== null) {
            $data['data']['media'] = $this->media;
        }
        
        if ($this->mediaList !== null) {
            $data['data']['media'] = $this->mediaList;
            $data['data']['pagination'] = [
                'total' => $this->total,
                'page' => $this->page,
                'totalPages' => $this->totalPages,
            ];
        }
        
        return $data;
    }
}
