<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: 'App\Repository\MediaRepository')]
#[ORM\Table(name: 'media')]
#[ORM\Index(columns: ['type'], name: 'idx_media_type')]
#[ORM\Index(columns: ['user_id'], name: 'idx_media_user')]
#[ORM\Index(columns: ['created_at'], name: 'idx_media_created')]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['media:read', 'media:admin'])]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['media:read', 'media:admin'])]
    private ?Uuid $uuid = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    #[Groups(['media:read', 'media:admin'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['image', 'video', 'icon', 'gif', 'audio', 'font', 'document'])]
    #[Groups(['media:read', 'media:admin'])]
    private ?string $type = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    #[Groups(['media:read', 'media:admin'])]
    private ?string $mime_type = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Groups(['media:read', 'media:admin'])]
    private ?int $size = null;

    #[ORM\Column(type: Types::STRING, length: 500)]
    #[Assert\NotBlank]
    #[Groups(['media:read', 'media:admin'])]
    private ?string $url = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    #[Groups(['media:read', 'media:admin'])]
    private ?string $thumbnail_url = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['media:read', 'media:admin'])]
    private ?int $width = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['media:read', 'media:admin'])]
    private ?int $height = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['media:read', 'media:admin'])]
    private ?int $duration = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['media:read', 'media:admin'])]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['media:read', 'media:admin'])]
    private array $tags = [];

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Choice(choices: ['unsplash', 'pexels', 'iconfinder', 'giphy', 'upload', 'generated'])]
    #[Groups(['media:read', 'media:admin'])]
    private ?string $source = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['media:read', 'media:admin'])]
    private ?string $source_id = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    #[Groups(['media:read', 'media:admin'])]
    private ?string $attribution = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    #[Groups(['media:read', 'media:admin'])]
    private ?string $license = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['media:read', 'media:admin'])]
    private bool $is_premium = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['media:admin'])]
    private bool $is_active = true;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'media')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[Groups(['media:admin'])]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['media:read', 'media:admin'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['media:admin'])]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['media:admin'])]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
        $this->metadata = [];
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mime_type;
    }

    public function setMimeType(string $mime_type): static
    {
        $this->mime_type = $mime_type;
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;
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

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): static
    {
        $this->height = $height;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
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

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function getSourceId(): ?string
    {
        return $this->source_id;
    }

    public function setSourceId(?string $source_id): static
    {
        $this->source_id = $source_id;
        return $this;
    }

    public function getAttribution(): ?string
    {
        return $this->attribution;
    }

    public function setAttribution(?string $attribution): static
    {
        $this->attribution = $attribution;
        return $this;
    }

    public function getLicense(): ?string
    {
        return $this->license;
    }

    public function setLicense(?string $license): static
    {
        $this->license = $license;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
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

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function delete(): static
    {
        $this->deletedAt = new \DateTimeImmutable();
        return $this;
    }

    public function restore(): static
    {
        $this->deletedAt = null;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updated_at = new \DateTimeImmutable();
    }

    /**
     * Convert the media entity to an array representation
     *
     * Returns a standardized array format for API responses and data serialization.
     * This method ensures consistent media data structure across all endpoints.
     *
     * @param bool $includeUser Whether to include user information in the response
     * @return array<string, mixed> Array representation of the media entity
     */
    public function toArray(bool $includeUser = false): array
    {
        $data = [
            'id' => $this->getId(),
            'uuid' => $this->getUuid()?->toRfc4122(),
            'name' => $this->getName(),
            'type' => $this->getType(),
            'mimeType' => $this->getMimeType(),
            'size' => $this->getSize(),
            'url' => $this->getUrl(),
            'thumbnail' => $this->getThumbnailUrl(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'duration' => $this->getDuration(),
            'source' => $this->getSource(),
            'sourceId' => $this->getSourceId(),
            'metadata' => $this->getMetadata(),
            'tags' => $this->getTags(),
            'attribution' => $this->getAttribution(),
            'license' => $this->getLicense(),
            'isPremium' => $this->isIsPremium(),
            'isActive' => $this->isIsActive(),
            'createdAt' => $this->getCreatedAt()?->format('c'),
            'updatedAt' => $this->getUpdatedAt()?->format('c'),
        ];

        if ($includeUser && $this->getUser()) {
            $data['uploadedBy'] = [
                'id' => $this->getUser()->getId(),
                'username' => $this->getUser()->getUsername(),
            ];
        }

        return $data;
    }
}
