<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateDesignThumbnailRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Thumbnail URL is required')]
        #[Assert\Url(message: 'Thumbnail must be a valid URL')]
        public readonly string $thumbnail,
    ) {}
}
