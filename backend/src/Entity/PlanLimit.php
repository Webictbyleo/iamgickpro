<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'plan_limits')]
class PlanLimit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['plan:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SubscriptionPlan::class, inversedBy: 'limits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SubscriptionPlan $plan = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['plan:read', 'plan:write'])]
    private string $type;

    #[ORM\Column(type: 'bigint')]
    #[Assert\GreaterThanOrEqual(-1)] // -1 means unlimited
    #[Groups(['plan:read', 'plan:write'])]
    private int $value;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Groups(['plan:read', 'plan:write'])]
    private ?string $unit = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['plan:read', 'plan:write'])]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlan(): ?SubscriptionPlan
    {
        return $this->plan;
    }

    public function setPlan(?SubscriptionPlan $plan): self
    {
        $this->plan = $plan;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Check if this limit is unlimited
     */
    public function isUnlimited(): bool
    {
        return $this->value === -1;
    }

    /**
     * Get formatted limit value for display
     */
    public function getFormattedValue(): string
    {
        if ($this->isUnlimited()) {
            return 'Unlimited';
        }

        if ($this->unit) {
            return $this->value . ' ' . $this->unit;
        }

        return (string) $this->value;
    }
}
