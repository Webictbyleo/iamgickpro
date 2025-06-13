<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for listing export jobs with filtering and pagination
 */
final readonly class ExportJobListRequestDTO
{
    public function __construct(
        #[Assert\Type('integer')]
        #[Assert\GreaterThanOrEqual(1)]
        public int $page = 1,

        #[Assert\Type('integer')]
        #[Assert\Range(min: 1, max: 50)]
        public int $limit = 20,

        #[Assert\Choice(choices: ['pending', 'processing', 'completed', 'failed', 'cancelled'], message: 'Invalid status')]
        public ?string $status = null,

        #[Assert\Choice(choices: ['png', 'jpg', 'svg', 'mp4', 'gif'], message: 'Invalid format')]
        public ?string $format = null,
    ) {}

    public static function fromRequest(\Symfony\Component\HttpFoundation\Request $request): self
    {
        return new self(
            page: (int) $request->query->get('page', 1),
            limit: (int) $request->query->get('limit', 20),
            status: $request->query->get('status'),
            format: $request->query->get('format'),
        );
    }
}
