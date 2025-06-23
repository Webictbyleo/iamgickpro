<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'subscription_plans')]
class SubscriptionPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['plan:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['plan:read', 'plan:write'])]
    private string $code;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Groups(['plan:read', 'plan:write'])]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['plan:read', 'plan:write'])]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    #[Groups(['plan:read', 'plan:write'])]
    private string $monthlyPrice;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    #[Groups(['plan:read', 'plan:write'])]
    private string $yearlyPrice;

    #[ORM\Column(type: 'string', length: 3)]
    #[Assert\NotBlank]
    #[Assert\Length(exactly: 3)]
    #[Groups(['plan:read', 'plan:write'])]
    private string $currency = 'USD';

    #[ORM\Column(type: 'boolean')]
    #[Groups(['plan:read', 'plan:write'])]
    private bool $isActive = true;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['plan:read', 'plan:write'])]
    private bool $isDefault = false;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    #[Groups(['plan:read', 'plan:write'])]
    private int $sortOrder = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['plan:read'])]
    private readonly \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['plan:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'plan', targetEntity: PlanLimit::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $limits;

    #[ORM\OneToMany(mappedBy: 'plan', targetEntity: PlanFeature::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $features;

    #[ORM\OneToMany(mappedBy: 'subscriptionPlan', targetEntity: UserSubscription::class)]
    private Collection $subscriptions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->limits = new ArrayCollection();
        $this->features = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
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

    public function getMonthlyPrice(): string
    {
        return $this->monthlyPrice;
    }

    public function setMonthlyPrice(string $monthlyPrice): self
    {
        $this->monthlyPrice = $monthlyPrice;
        $this->touch();
        return $this;
    }

    public function getYearlyPrice(): string
    {
        return $this->yearlyPrice;
    }

    public function setYearlyPrice(string $yearlyPrice): self
    {
        $this->yearlyPrice = $yearlyPrice;
        $this->touch();
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = strtoupper($currency);
        $this->touch();
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        $this->touch();
        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;
        $this->touch();
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        $this->touch();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getLimits(): Collection
    {
        return $this->limits;
    }

    public function addLimit(PlanLimit $limit): self
    {
        if (!$this->limits->contains($limit)) {
            $this->limits->add($limit);
            $limit->setPlan($this);
        }
        return $this;
    }

    public function removeLimit(PlanLimit $limit): self
    {
        if ($this->limits->removeElement($limit)) {
            if ($limit->getPlan() === $this) {
                $limit->setPlan(null);
            }
        }
        return $this;
    }

    public function getFeatures(): Collection
    {
        return $this->features;
    }

    public function addFeature(PlanFeature $feature): self
    {
        if (!$this->features->contains($feature)) {
            $this->features->add($feature);
            $feature->setPlan($this);
        }
        return $this;
    }

    public function removeFeature(PlanFeature $feature): self
    {
        if ($this->features->removeElement($feature)) {
            if ($feature->getPlan() === $this) {
                $feature->setPlan(null);
            }
        }
        return $this;
    }

    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(UserSubscription $subscription): self
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setSubscriptionPlan($this);
        }
        return $this;
    }

    public function removeSubscription(UserSubscription $subscription): self
    {
        if ($this->subscriptions->removeElement($subscription)) {
            if ($subscription->getSubscriptionPlan() === $this) {
                $subscription->setSubscriptionPlan(null);
            }
        }
        return $this;
    }

    /**
     * Get a specific limit value by type
     */
    public function getLimitValue(string $type): int
    {
        foreach ($this->limits as $limit) {
            if ($limit->getType() === $type) {
                return $limit->getValue();
            }
        }
        return 0; // Default to 0 if limit not found
    }

    /**
     * Check if a feature is enabled
     */
    public function hasFeature(string $featureCode): bool
    {
        foreach ($this->features as $feature) {
            if ($feature->getCode() === $featureCode && $feature->isEnabled()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get yearly savings percentage
     */
    public function getYearlySavingsPercentage(): float
    {
        $monthlyTotal = (float) $this->monthlyPrice * 12;
        $yearlyPrice = (float) $this->yearlyPrice;
        
        if ($monthlyTotal <= 0 || $yearlyPrice <= 0) {
            return 0.0;
        }
        
        return round((($monthlyTotal - $yearlyPrice) / $monthlyTotal) * 100, 1);
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
