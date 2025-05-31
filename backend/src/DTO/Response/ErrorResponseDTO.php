<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Error response DTO for API errors
 */
class ErrorResponseDTO extends BaseResponseDTO
{
    public function __construct(
        string $message,
        public readonly array $details = [],
        public readonly ?string $code = null,
        ?string $timestamp = null
    ) {
        parent::__construct($message, false, $timestamp);
    }

    public function toArray(): array
    {
        $data = [
            'success' => $this->success,
            'error' => $this->message,
            'timestamp' => $this->timestamp ?? (new \DateTimeImmutable())->format('c'),
        ];

        if (!empty($this->details)) {
            $data['details'] = $this->details;
        }

        if ($this->code !== null) {
            $data['code'] = $this->code;
        }

        return $data;
    }
}
