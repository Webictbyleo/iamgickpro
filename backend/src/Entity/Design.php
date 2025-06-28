<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'designs')]
class Design
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['design:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Groups(['design:read', 'design:write'])]
    private string $title;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Groups(['design:read', 'design:write'])]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['design:read', 'design:write'])]
    private ?string $description = null;

    #[ORM\Column(type: 'json')]
    #[Groups(['design:read', 'design:write'])]
    private array $data = [];

    #[ORM\Column(type: 'integer')]
    #[Assert\GreaterThan(0)]
    #[Groups(['design:read', 'design:write'])]
    private int $width = 800;

    #[ORM\Column(type: 'integer')]
    #[Assert\GreaterThan(0)]
    #[Groups(['design:read', 'design:write'])]
    private int $height = 600;

    #[ORM\Column(type: 'json')]
    #[Groups(['design:read', 'design:write'])]
    private array $background = ['type' => 'color', 'color' => '#ffffff'];

    #[ORM\Column(type: 'json')]
    #[Groups(['design:read', 'design:write'])]
    private array $animationSettings = [];

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    #[Groups(['design:read'])]
    private readonly string $uuid;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['design:read'])]
    private readonly \DateTimeImmutable $createdAt;
    

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['design:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'designs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\OneToMany(mappedBy: 'design', targetEntity: Layer::class, orphanRemoval: true)]
    #[ORM\OrderBy(['zIndex' => 'ASC'])]
    private Collection $layers;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['design:read', 'design:write'])]
    private bool $hasAnimation = false;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['design:read', 'design:write'])]
    private ?float $fps = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['design:read', 'design:write'])]
    private ?float $duration = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Groups(['design:read', 'design:write'])]
    private ?string $thumbnail = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['design:read', 'design:write'])]
    private bool $isPublic = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['design:admin'])]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->uuid = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->layers = new ArrayCollection();
        $this->title = 'Untitled Design';
        $this->name = $this->title; // Keep name in sync with title
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        $this->touch();
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        // Keep title in sync with name for backward compatibility
        $this->title = $name;
        $this->touch();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        $this->touch();
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        $this->touch();
        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): self
    {
        $this->width = $width;
        $this->touch();
        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): self
    {
        $this->height = $height;
        $this->touch();
        return $this;
    }

    public function getCanvasWidth(): int
    {
        return $this->width;
    }

    public function setCanvasWidth(int $width): self
    {
        return $this->setWidth($width);
    }

    public function getCanvasHeight(): int
    {
        return $this->height;
    }

    public function setCanvasHeight(int $height): self
    {
        return $this->setHeight($height);
    }

    public function getBackground(): array
    {
        return $this->background;
    }

    public function setBackground(array $background): self
    {
        $this->background = $background;
        $this->touch();
        return $this;
    }

    public function getAnimationSettings(): array
    {
        return $this->animationSettings;
    }

    public function setAnimationSettings(array $animationSettings): self
    {
        $this->animationSettings = $animationSettings;
        $this->touch();
        return $this;
    }

    public function  getCanvasSettings(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'background' => $this->background,
            'animationSettings' => $this->animationSettings
        ];
    }

    public function setCanvasSettings(array $settings): self
    {
        if (isset($settings['width'])) {
            $this->setWidth($settings['width']);
        }
        if (isset($settings['height'])) {
            $this->setHeight($settings['height']);
        }
        if (isset($settings['background'])) {
            $this->setBackground($settings['background']);
        }
        if (isset($settings['animationSettings'])) {
            $this->setAnimationSettings($settings['animationSettings']);
        }
        return $this;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;
        $this->touch();
        return $this;
    }

    public function getLayers(): Collection
    {
        return $this->layers;
    }

    public function addLayer(Layer $layer): self
    {
        if (!$this->layers->contains($layer)) {
            $this->layers->add($layer);
            $layer->setDesign($this);
        }

        return $this;
    }

    public function removeLayer(Layer $layer): self
    {
        if ($this->layers->removeElement($layer)) {
            if ($layer->getDesign() === $this) {
                $layer->setDesign(null);
            }
        }

        return $this;
    }

    public function getHasAnimation(): bool
    {
        return $this->hasAnimation;
    }

    public function getHasAnimations(): bool
    {
        return $this->hasAnimation;
    }

    public function setHasAnimation(bool $hasAnimation): self
    {
        $this->hasAnimation = $hasAnimation;
        $this->touch();
        return $this;
    }

    public function setHasAnimations(bool $hasAnimations): self
    {
        return $this->setHasAnimation($hasAnimations);
    }

    public function getFps(): ?float
    {
        return $this->fps;
    }

    public function setFps(?float $fps): self
    {
        $this->fps = $fps;
        $this->touch();
        return $this;
    }

    public function getDuration(): ?float
    {
        return $this->duration;
    }

    public function setDuration(?float $duration): self
    {
        $this->duration = $duration;
        $this->touch();
        return $this;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(?string $thumbnail): self
    {
        $this->thumbnail = $thumbnail;
        $this->touch();
        return $this;
    }

    public function getIsPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;
        $this->touch();
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function delete(): self
    {
        $this->deletedAt = new \DateTimeImmutable();
        return $this;
    }

    public function restore(): self
    {
        $this->deletedAt = null;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
