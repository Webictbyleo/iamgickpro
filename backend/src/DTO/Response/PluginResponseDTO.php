<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Response DTO for Plugin data
 */
class PluginResponseDTO extends BaseResponseDTO
{
    public function __construct(
        bool $success,
        string $message,
        public readonly ?array $plugin = null,
        public readonly ?array $plugins = null,
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
        
        if ($this->plugin !== null) {
            $data['data']['plugin'] = $this->plugin;
        }
        
        if ($this->plugins !== null) {
            $data['data']['plugins'] = $this->plugins;
            $data['data']['pagination'] = [
                'total' => $this->total,
                'page' => $this->page,
                'totalPages' => $this->totalPages,
            ];
        }
        
        return $data;
    }
}
