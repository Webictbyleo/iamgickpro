<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: 'App\Repository\TemplateRepository')]
#[ORM\Table(name: 'templates')]
#[ORM\Index(columns: ['category'], name: 'idx_template_category')]
#[ORM\Index(columns: ['is_premium'], name: 'idx_template_premium')]
#[ORM\Index(columns: ['created_at'], name: 'idx_template_created')]
class Template
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['template:read', 'template:admin'])]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['template:read', 'template:admin'])]
    private ?Uuid $uuid = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    #[Groups(['template:read', 'template:admin'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['template:read', 'template:admin'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: [
        'social-media',
        'presentation',
        'print',
        'marketing',
        'document',
        'logo',
        'web-graphics',
        'video',
        'animation'
    ])]
    #[Groups(['template:read', 'template:admin'])]
    private ?string $category = null;

    #[ORM\Column(type: Types::JSON)]
    #[Assert\NotBlank]
    #[Groups(['template:read', 'template:admin'])]
    private array $tags = [];

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Groups(['template:read', 'template:admin'])]
    private ?int $width = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Groups(['template:read', 'template:admin'])]
    private ?int $height = null;

    #[ORM\Column(type: Types::JSON)]
    #[Assert\NotBlank]
    #[Groups(['template:read', 'template:admin'])]
    private array $canvas_settings = [];

    #[ORM\Column(type: Types::JSON)]
    #[Assert\NotBlank]
    #[Groups(['template:read', 'template:admin'])]
    private array $layers = [];

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    #[Groups(['template:read', 'template:admin'])]
    private ?string $thumbnail_url = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    #[Groups(['template:read', 'template:admin'])]
    private ?string $preview_url = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['template:read', 'template:admin'])]
    private bool $is_premium = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['template:admin'])]
    private bool $is_active = true;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['template:read', 'template:admin'])]
    private int $usage_count = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, options: ['default' => '0.00'])]
    #[Groups(['template:read', 'template:admin'])]
    private string $rating = '0.00';

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['template:read', 'template:admin'])]
    private int $rating_count = 0;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['template:admin'])]
    private ?User $created_by = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['template:read', 'template:admin'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['template:admin'])]
    private ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): static
    {
        $this->tags = $tags;
        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(int $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(int $height): static
    {
        $this->height = $height;
        return $this;
    }

    public function getCanvasSettings(): array
    {
        return $this->canvas_settings;
    }

    public function setCanvasSettings(array $canvas_settings): static
    {
        $this->canvas_settings = $canvas_settings;
        return $this;
    }

    public function getLayers(): array
    {
        return $this->layers;
    }

    public function setLayers(array $layers): static
    {
        $this->layers = $layers;
        return $this;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnail_url;
    }

    public function setThumbnailUrl(?string $thumbnail_url): static
    {
        $this->thumbnail_url = $thumbnail_url;
        return $this;
    }

    public function getPreviewUrl(): ?string
    {
        return $this->preview_url;
    }

    public function setPreviewUrl(?string $preview_url): static
    {
        $this->preview_url = $preview_url;
        return $this;
    }

    public function isIsPremium(): bool
    {
        return $this->is_premium;
    }

    public function setIsPremium(bool $is_premium): static
    {
        $this->is_premium = $is_premium;
        return $this;
    }

    public function isIsActive(): bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): static
    {
        $this->is_active = $is_active;
        return $this;
    }

    public function getUsageCount(): int
    {
        return $this->usage_count;
    }

    public function setUsageCount(int $usage_count): static
    {
        $this->usage_count = $usage_count;
        return $this;
    }

    public function incrementUsageCount(): static
    {
        $this->usage_count++;
        return $this;
    }

    public function getRating(): string
    {
        return $this->rating;
    }

    public function setRating(string $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getRatingCount(): int
    {
        return $this->rating_count;
    }

    public function setRatingCount(int $rating_count): static
    {
        $this->rating_count = $rating_count;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->created_by;
    }

    public function setCreatedBy(?User $created_by): static
    {
        $this->created_by = $created_by;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updated_at = new \DateTimeImmutable();
    }
}
