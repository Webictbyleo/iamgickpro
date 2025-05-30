<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: 'App\Repository\PluginRepository')]
#[ORM\Table(name: 'plugins')]
#[ORM\Index(columns: ['status'], name: 'idx_plugin_status')]
#[ORM\Index(columns: ['user_id'], name: 'idx_plugin_user')]
#[ORM\Index(columns: ['created_at'], name: 'idx_plugin_created')]
class Plugin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private ?Uuid $uuid = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-z0-9\-]+$/')]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private ?string $identifier = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d+\.\d+\.\d+$/')]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private ?string $version = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['pending', 'approved', 'rejected', 'suspended'])]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private ?string $status = 'pending';

    #[ORM\Column(type: Types::JSON)]
    #[Assert\NotBlank]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private array $manifest = [];

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private array $permissions = [];

    #[ORM\Column(type: Types::STRING, length: 500)]
    #[Assert\NotBlank]
    #[Assert\Url]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private ?string $entry_point = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private ?string $icon_url = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private ?string $banner_url = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private array $categories = [];

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private array $tags = [];

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private bool $is_premium = false;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private ?string $price = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private int $install_count = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, options: ['default' => '0.00'])]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private string $rating = '0.00';

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private int $rating_count = 0;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['plugin:admin'])]
    private ?array $security_scan = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plugin:admin'])]
    private ?string $review_notes = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['plugin:admin'])]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['plugin:admin'])]
    private ?User $reviewed_by = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['plugin:admin'])]
    private ?\DateTimeImmutable $reviewed_at = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['plugin:read', 'plugin:admin'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['plugin:admin'])]
    private ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
        $this->manifest = [];
        $this->permissions = [];
        $this->categories = [];
        $this->tags = [];
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

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): static
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): static
    {
        $this->version = $version;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getManifest(): array
    {
        return $this->manifest;
    }

    public function setManifest(array $manifest): static
    {
        $this->manifest = $manifest;
        return $this;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function setPermissions(array $permissions): static
    {
        $this->permissions = $permissions;
        return $this;
    }

    public function getEntryPoint(): ?string
    {
        return $this->entry_point;
    }

    public function setEntryPoint(string $entry_point): static
    {
        $this->entry_point = $entry_point;
        return $this;
    }

    public function getIconUrl(): ?string
    {
        return $this->icon_url;
    }

    public function setIconUrl(?string $icon_url): static
    {
        $this->icon_url = $icon_url;
        return $this;
    }

    public function getBannerUrl(): ?string
    {
        return $this->banner_url;
    }

    public function setBannerUrl(?string $banner_url): static
    {
        $this->banner_url = $banner_url;
        return $this;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function setCategories(array $categories): static
    {
        $this->categories = $categories;
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

    public function isIsPremium(): bool
    {
        return $this->is_premium;
    }

    public function setIsPremium(bool $is_premium): static
    {
        $this->is_premium = $is_premium;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getInstallCount(): int
    {
        return $this->install_count;
    }

    public function setInstallCount(int $install_count): static
    {
        $this->install_count = $install_count;
        return $this;
    }

    public function incrementInstallCount(): static
    {
        $this->install_count++;
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

    public function getSecurityScan(): ?array
    {
        return $this->security_scan;
    }

    public function setSecurityScan(?array $security_scan): static
    {
        $this->security_scan = $security_scan;
        return $this;
    }

    public function getReviewNotes(): ?string
    {
        return $this->review_notes;
    }

    public function setReviewNotes(?string $review_notes): static
    {
        $this->review_notes = $review_notes;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getReviewedBy(): ?User
    {
        return $this->reviewed_by;
    }

    public function setReviewedBy(?User $reviewed_by): static
    {
        $this->reviewed_by = $reviewed_by;
        return $this;
    }

    public function getReviewedAt(): ?\DateTimeImmutable
    {
        return $this->reviewed_at;
    }

    public function setReviewedAt(?\DateTimeImmutable $reviewed_at): static
    {
        $this->reviewed_at = $reviewed_at;
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
