<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'plan_features')]
class PlanFeature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['plan:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SubscriptionPlan::class, inversedBy: 'features')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SubscriptionPlan $plan = null;

    #[ORM\Column(type: 'string', length: 50)]
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

    #[ORM\Column(type: 'boolean')]
    #[Groups(['plan:read', 'plan:write'])]
    private bool $enabled = true;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['plan:read', 'plan:write'])]
    private ?array $config = null;

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

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getConfig(): ?array
    {
        return $this->config;
    }

    public function setConfig(?array $config): self
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Get a specific config value
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set a specific config value
     */
    public function setConfigValue(string $key, mixed $value): self
    {
        if ($this->config === null) {
            $this->config = [];
        }
        $this->config[$key] = $value;
        return $this;
    }
}
