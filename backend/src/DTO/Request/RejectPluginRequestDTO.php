<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RejectPluginRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Rejection reason is required')]
        #[Assert\Length(min: 10, max: 500, minMessage: 'Rejection reason must be at least 10 characters long', maxMessage: 'Rejection reason cannot exceed 500 characters')]
        public string $reason,
    ) {}
}
