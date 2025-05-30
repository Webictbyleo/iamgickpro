<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'layers')]
class Layer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['layer:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Groups(['layer:read', 'layer:write'])]
    private string $name;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['text', 'image', 'video', 'svg', 'shape'])]
    #[Groups(['layer:read', 'layer:write'])]
    private string $type;

    #[ORM\Column(type: 'json')]
    #[Groups(['layer:read', 'layer:write'])]
    private array $properties = [];

    #[ORM\Column(type: 'json')]
    #[Groups(['layer:read', 'layer:write'])]
    private array $transform = [
        'x' => 0,
        'y' => 0,
        'width' => 100,
        'height' => 100,
        'rotation' => 0,
        'scaleX' => 1,
        'scaleY' => 1
    ];

    #[ORM\Column(type: 'integer')]
    #[Groups(['layer:read', 'layer:write'])]
    private int $zIndex = 0;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['layer:read', 'layer:write'])]
    private bool $visible = true;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['layer:read', 'layer:write'])]
    private bool $locked = false;

    #[ORM\Column(type: 'float')]
    #[Assert\Range(min: 0, max: 1)]
    #[Groups(['layer:read', 'layer:write'])]
    private float $opacity = 1.0;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['layer:read', 'layer:write'])]
    private ?array $animations = null;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    #[Groups(['layer:read'])]
    private readonly string $uuid;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['layer:read'])]
    private readonly \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['layer:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Design::class, inversedBy: 'layers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Design $design = null;

    #[ORM\ManyToOne(targetEntity: Layer::class, inversedBy: 'children')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Layer $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: Layer::class, orphanRemoval: true)]
    private Collection $children;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['layer:read', 'layer:write'])]
    private ?array $mask = null;

    public function __construct()
    {
        $this->uuid = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->touch();
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        $this->touch();
        return $this;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): self
    {
        $this->properties = $properties;
        $this->touch();
        return $this;
    }

    public function getTransform(): array
    {
        return $this->transform;
    }

    public function setTransform(array $transform): self
    {
        $this->transform = $transform;
        $this->touch();
        return $this;
    }

    public function getZIndex(): int
    {
        return $this->zIndex;
    }

    public function setZIndex(int $zIndex): self
    {
        $this->zIndex = $zIndex;
        $this->touch();
        return $this;
    }

    public function getVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;
        $this->touch();
        return $this;
    }

    public function getLocked(): bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): self
    {
        $this->locked = $locked;
        $this->touch();
        return $this;
    }

    public function getOpacity(): float
    {
        return $this->opacity;
    }

    public function setOpacity(float $opacity): self
    {
        $this->opacity = $opacity;
        $this->touch();
        return $this;
    }

    public function getAnimations(): ?array
    {
        return $this->animations;
    }

    public function setAnimations(?array $animations): self
    {
        $this->animations = $animations;
        $this->touch();
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

    public function getDesign(): ?Design
    {
        return $this->design;
    }

    public function setDesign(?Design $design): self
    {
        $this->design = $design;
        $this->touch();
        return $this;
    }

    public function getParent(): ?Layer
    {
        return $this->parent;
    }

    public function setParent(?Layer $parent): self
    {
        $this->parent = $parent;
        $this->touch();
        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(Layer $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(Layer $child): self
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    public function getMask(): ?array
    {
        return $this->mask;
    }

    public function setMask(?array $mask): self
    {
        $this->mask = $mask;
        $this->touch();
        return $this;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Individual transform property getters/setters for better API compatibility
    public function getX(): float
    {
        return (float) ($this->transform['x'] ?? 0);
    }

    public function setX(float $x): self
    {
        $this->transform['x'] = $x;
        $this->touch();
        return $this;
    }

    public function getY(): float
    {
        return (float) ($this->transform['y'] ?? 0);
    }

    public function setY(float $y): self
    {
        $this->transform['y'] = $y;
        $this->touch();
        return $this;
    }

    public function getWidth(): float
    {
        return (float) ($this->transform['width'] ?? 100);
    }

    public function setWidth(float $width): self
    {
        $this->transform['width'] = $width;
        $this->touch();
        return $this;
    }

    public function getHeight(): float
    {
        return (float) ($this->transform['height'] ?? 100);
    }

    public function setHeight(float $height): self
    {
        $this->transform['height'] = $height;
        $this->touch();
        return $this;
    }

    public function getRotation(): float
    {
        return (float) ($this->transform['rotation'] ?? 0);
    }

    public function setRotation(float $rotation): self
    {
        $this->transform['rotation'] = $rotation;
        $this->touch();
        return $this;
    }

    public function getScaleX(): float
    {
        return (float) ($this->transform['scaleX'] ?? 1);
    }

    public function setScaleX(float $scaleX): self
    {
        $this->transform['scaleX'] = $scaleX;
        $this->touch();
        return $this;
    }

    public function getScaleY(): float
    {
        return (float) ($this->transform['scaleY'] ?? 1);
    }

    public function setScaleY(float $scaleY): self
    {
        $this->transform['scaleY'] = $scaleY;
        $this->touch();
        return $this;
    }

    // Data property alias for properties array
    public function getData(): array
    {
        return $this->properties;
    }

    public function setData(array $data): self
    {
        $this->properties = $data;
        $this->touch();
        return $this;
    }

    // Boolean method aliases for consistency
    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }
}
