<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'user_subscriptions')]
class UserSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['subscription:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: SubscriptionPlan::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SubscriptionPlan $subscriptionPlan = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['active', 'canceled', 'expired', 'trial', 'past_due'])]
    #[Groups(['subscription:read', 'subscription:write'])]
    private string $status = 'active';

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['monthly', 'yearly'])]
    #[Groups(['subscription:read', 'subscription:write'])]
    private string $billingPeriod = 'monthly';

    #[ORM\Column(type: 'datetime_immutable')]
    #[Assert\NotNull]
    #[Groups(['subscription:read', 'subscription:write'])]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['subscription:read', 'subscription:write'])]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['subscription:read', 'subscription:write'])]
    private ?\DateTimeImmutable $trialEndDate = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['subscription:read'])]
    private ?\DateTimeImmutable $canceledAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['subscription:read', 'subscription:write'])]
    private ?string $externalId = null; // For Stripe, PayPal, etc.

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['subscription:read', 'subscription:write'])]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['subscription:read'])]
    private readonly \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['subscription:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->startDate = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        $this->touch();
        return $this;
    }

    public function getSubscriptionPlan(): ?SubscriptionPlan
    {
        return $this->subscriptionPlan;
    }

    public function setSubscriptionPlan(?SubscriptionPlan $subscriptionPlan): self
    {
        $this->subscriptionPlan = $subscriptionPlan;
        $this->touch();
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->touch();
        return $this;
    }

    public function getBillingPeriod(): string
    {
        return $this->billingPeriod;
    }

    public function setBillingPeriod(string $billingPeriod): self
    {
        $this->billingPeriod = $billingPeriod;
        $this->touch();
        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;
        $this->touch();
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;
        $this->touch();
        return $this;
    }

    public function getTrialEndDate(): ?\DateTimeImmutable
    {
        return $this->trialEndDate;
    }

    public function setTrialEndDate(?\DateTimeImmutable $trialEndDate): self
    {
        $this->trialEndDate = $trialEndDate;
        $this->touch();
        return $this;
    }

    public function getCanceledAt(): ?\DateTimeImmutable
    {
        return $this->canceledAt;
    }

    public function setCanceledAt(?\DateTimeImmutable $canceledAt): self
    {
        $this->canceledAt = $canceledAt;
        $this->touch();
        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;
        $this->touch();
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
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

    /**
     * Check if subscription is currently active
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = new \DateTimeImmutable();
        
        // Check if subscription has expired
        if ($this->endDate && $this->endDate < $now) {
            return false;
        }

        return true;
    }

    /**
     * Check if subscription is in trial period
     */
    public function isInTrial(): bool
    {
        if (!$this->trialEndDate) {
            return false;
        }

        $now = new \DateTimeImmutable();
        return $this->trialEndDate > $now;
    }

    /**
     * Get days remaining in trial
     */
    public function getTrialDaysRemaining(): int
    {
        if (!$this->isInTrial()) {
            return 0;
        }

        $now = new \DateTimeImmutable();
        $interval = $now->diff($this->trialEndDate);
        return (int) $interval->days;
    }

    /**
     * Get days until expiration
     */
    public function getDaysUntilExpiration(): ?int
    {
        if (!$this->endDate) {
            return null; // No expiration
        }

        $now = new \DateTimeImmutable();
        if ($this->endDate < $now) {
            return 0; // Already expired
        }

        $interval = $now->diff($this->endDate);
        return (int) $interval->days;
    }

    /**
     * Cancel the subscription
     */
    public function cancel(): self
    {
        $this->status = 'canceled';
        $this->canceledAt = new \DateTimeImmutable();
        $this->touch();
        return $this;
    }

    /**
     * Expire the subscription
     */
    public function expire(): self
    {
        $this->status = 'expired';
        $this->touch();
        return $this;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
