<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Response DTO for Layer data
 */
class LayerResponseDTO extends BaseResponseDTO
{
    public function __construct(
        bool $success,
        string $message,
        public readonly ?array $layer = null,
        public readonly ?array $layers = null,
        ?\DateTimeImmutable $timestamp = null
    ) {
        parent::__construct($success, $message, $timestamp);
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        
        if ($this->layer !== null) {
            $data['data']['layer'] = $this->layer;
        }
        
        if ($this->layers !== null) {
            $data['data']['layers'] = $this->layers;
        }
        
        return $data;
    }
}
