<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Shape Entity
 * 
 * Stores metadata and references for SVG shapes that can be used in designs.
 * Shapes are categorized and have searchable keywords for easy discovery.
 */
#[ORM\Entity]
#[ORM\Table(name: 'shapes')]
#[ORM\Index(columns: ['category'], name: 'idx_shape_category')]
#[ORM\Index(columns: ['shape_category'], name: 'idx_shape_shape_category')]
#[ORM\Index(columns: ['original_filename'], name: 'idx_shape_filename')]
#[ORM\Index(columns: ['file_size'], name: 'idx_shape_file_size')]
class Shape
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['shape:read', 'shape:list'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['shape:read', 'shape:list'])]
    private string $originalFilename;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['shape:read', 'shape:list'])]
    private string $normalizedFilename;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Groups(['shape:read', 'shape:list'])]
    private string $category;

    #[ORM\Column(type: Types::STRING, length: 500)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 500)]
    #[Groups(['shape:read', 'shape:list'])]
    private string $path;

    #[ORM\Column(type: Types::STRING, length: 500)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 500)]
    #[Groups(['shape:read'])]
    private string $originalPath;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['shape:read', 'shape:list'])]
    private array $keywords = [];

    #[ORM\Column(type: Types::STRING, length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    #[Groups(['shape:read', 'shape:list'])]
    private string $shapeCategory;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Groups(['shape:read', 'shape:list'])]
    private string $description;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\PositiveOrZero]
    #[Groups(['shape:read'])]
    private int $fileSize;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['shape:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['shape:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): static
    {
        $this->originalFilename = $originalFilename;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getNormalizedFilename(): string
    {
        return $this->normalizedFilename;
    }

    public function setNormalizedFilename(string $normalizedFilename): static
    {
        $this->normalizedFilename = $normalizedFilename;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getOriginalPath(): string
    {
        return $this->originalPath;
    }

    public function setOriginalPath(string $originalPath): static
    {
        $this->originalPath = $originalPath;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function setKeywords(array $keywords): static
    {
        $this->keywords = $keywords;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function addKeyword(string $keyword): static
    {
        if (!in_array($keyword, $this->keywords, true)) {
            $this->keywords[] = $keyword;
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function removeKeyword(string $keyword): static
    {
        $key = array_search($keyword, $this->keywords, true);
        if ($key !== false) {
            unset($this->keywords[$key]);
            $this->keywords = array_values($this->keywords); // Re-index
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getShapeCategory(): string
    {
        return $this->shapeCategory;
    }

    public function setShapeCategory(string $shapeCategory): static
    {
        $this->shapeCategory = $shapeCategory;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): static
    {
        $this->fileSize = $fileSize;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Get the absolute file system path to the shape SVG file
     */
    public function getAbsolutePath(): string
    {
        return '/var/www/html/iamgickpro/backend/storage/shapes/' . $this->path;
    }

    /**
     * Get the web-accessible URL for the shape SVG file
     */
    public function getUrl(): string
    {
        return '/storage/shapes/' . $this->path;
    }

    /**
     * Get shape data formatted for stock media response
     */
    public function toStockMediaFormat(): array
    {
        return [
            'id' => 'shape_' . $this->id,
            'name' => $this->originalFilename,
            'type' => 'shape',
            'mimeType' => 'image/svg+xml',
            'size' => $this->fileSize,
            'url' => $this->getUrl(),
            'thumbnailUrl' => $this->getUrl(), // SVG can serve as its own thumbnail
            'width' => null, // SVG is scalable
            'height' => null, // SVG is scalable
            'duration' => null,
            'source' => 'internal',
            'sourceId' => (string) $this->id,
            'license' => 'Free for commercial use',
            'attribution' => null,
            'tags' => array_merge($this->keywords, [$this->category, $this->shapeCategory]),
            'isPremium' => false,
            'metadata' => [
                'category' => $this->category,
                'shapeCategory' => $this->shapeCategory,
                'description' => $this->description,
                'keywords' => $this->keywords,
                'filename' => $this->normalizedFilename,
                'originalFilename' => $this->originalFilename
            ]
        ];
    }
}
