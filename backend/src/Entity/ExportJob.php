<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'App\Repository\ExportJobRepository')]
#[ORM\Table(name: 'export_jobs')]
#[ORM\Index(columns: ['status'], name: 'idx_export_status')]
#[ORM\Index(columns: ['created_at'], name: 'idx_export_created')]
#[ORM\Index(columns: ['user_id', 'status'], name: 'idx_user_export_status')]
class ExportJob
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const FORMAT_PNG = 'png';
    public const FORMAT_JPEG = 'jpeg';
    public const FORMAT_SVG = 'svg';
    public const FORMAT_GIF = 'gif';
    public const FORMAT_MP4 = 'mp4';
    public const FORMAT_WEBM = 'webm';

    public const QUALITY_LOW = 'low';
    public const QUALITY_MEDIUM = 'medium';
    public const QUALITY_HIGH = 'high';
    public const QUALITY_ULTRA = 'ultra';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['export:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['export:read'])]
    private Uuid $uuid;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'exportJobs')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['export:read'])]
    private readonly User $user;

    #[ORM\ManyToOne(targetEntity: Design::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['export:read'])]
    private readonly Design $design;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\Choice(choices: [
        self::FORMAT_PNG,
        self::FORMAT_JPEG,
        self::FORMAT_SVG,
        self::FORMAT_GIF,
        self::FORMAT_MP4,
        self::FORMAT_WEBM
    ])]
    #[Groups(['export:read'])]
    private readonly string $format;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\Choice(choices: [
        self::QUALITY_LOW,
        self::QUALITY_MEDIUM,
        self::QUALITY_HIGH,
        self::QUALITY_ULTRA
    ])]
    #[Groups(['export:read'])]
    private readonly string $quality;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 100, max: 8000)]
    #[Groups(['export:read'])]
    private readonly ?int $width;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 100, max: 8000)]
    #[Groups(['export:read'])]
    private readonly ?int $height;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 1, max: 10)]
    #[Groups(['export:read'])]
    private readonly ?int $scale;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['export:read'])]
    private readonly bool $transparent;

    #[ORM\Column(type: Types::STRING, length: 7, nullable: true)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/')]
    #[Groups(['export:read'])]
    private readonly ?string $backgroundColor;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['export:read'])]
    private readonly ?array $animationSettings;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\Choice(choices: [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED
    ])]
    #[Groups(['export:read'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['export:read'])]
    private int $progress = 0;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['export:read'])]
    private ?string $filePath = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Groups(['export:read'])]
    private ?string $fileName = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    #[Groups(['export:read'])]
    private ?int $fileSize = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Groups(['export:read'])]
    private ?string $mimeType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['export:read'])]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['export:read'])]
    private ?array $errorDetails = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['export:read'])]
    private ?int $processingTimeMs = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['export:read'])]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['export:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['export:read'])]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['export:read'])]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['export:read'])]
    private ?\DateTimeImmutable $expiresAt = null;

    public function __construct(
        User $user,
        Design $design,
        string $format,
        string $quality = self::QUALITY_MEDIUM,
        ?int $width = null,
        ?int $height = null,
        ?int $scale = null,
        bool $transparent = false,
        ?string $backgroundColor = null,
        ?array $animationSettings = null
    ) {
        $this->uuid = Uuid::v4();
        $this->user = $user;
        $this->design = $design;
        $this->format = $format;
        $this->quality = $quality;
        $this->width = $width;
        $this->height = $height;
        $this->scale = $scale;
        $this->transparent = $transparent;
        $this->backgroundColor = $backgroundColor;
        $this->animationSettings = $animationSettings;
        $this->createdAt = new \DateTimeImmutable();
        
        // Set expiration based on format (longer for videos)
        $hoursToExpire = in_array($format, [self::FORMAT_MP4, self::FORMAT_WEBM]) ? 72 : 24;
        $this->expiresAt = $this->createdAt->modify("+{$hoursToExpire} hours");
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getDesign(): Design
    {
        return $this->design;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getQuality(): string
    {
        return $this->quality;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getScale(): ?int
    {
        return $this->scale;
    }

    public function isTransparent(): bool
    {
        return $this->transparent;
    }

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function getAnimationSettings(): ?array
    {
        return $this->animationSettings;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        
        if ($status === self::STATUS_PROCESSING && $this->startedAt === null) {
            $this->startedAt = new \DateTimeImmutable();
        }
        
        if (in_array($status, [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED])) {
            $this->completedAt = new \DateTimeImmutable();
            
            if ($this->startedAt !== null) {
                $this->processingTimeMs = (int) (
                    ($this->completedAt->getTimestamp() - $this->startedAt->getTimestamp()) * 1000
                );
            }
        }
        
        return $this;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): self
    {
        $this->progress = max(0, min(100, $progress));
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getErrorDetails(): ?array
    {
        return $this->errorDetails;
    }

    public function setErrorDetails(?array $errorDetails): self
    {
        $this->errorDetails = $errorDetails;
        return $this;
    }

    public function getProcessingTimeMs(): ?int
    {
        return $this->processingTimeMs;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && new \DateTimeImmutable() > $this->expiresAt;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED
        ]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function isAnimated(): bool
    {
        return in_array($this->format, [self::FORMAT_GIF, self::FORMAT_MP4, self::FORMAT_WEBM]);
    }

    public function getDurationSeconds(): ?float
    {
        if (!$this->isAnimated() || !$this->animationSettings) {
            return null;
        }

        return $this->animationSettings['duration'] ?? null;
    }

    public function getFps(): ?int
    {
        if (!$this->isAnimated() || !$this->animationSettings) {
            return null;
        }

        return $this->animationSettings['fps'] ?? null;
    }

    public function cancel(): self
    {
        if ($this->canBeCancelled()) {
            $this->setStatus(self::STATUS_CANCELLED);
        }
        
        return $this;
    }

    public function markAsFailed(string $message, ?array $details = null): self
    {
        $this->setStatus(self::STATUS_FAILED);
        $this->setErrorMessage($message);
        $this->setErrorDetails($details);
        
        return $this;
    }

    public function markAsCompleted(
        string $filePath,
        string $fileName,
        int $fileSize,
        string $mimeType,
        ?array $metadata = null
    ): self {
        $this->setStatus(self::STATUS_COMPLETED);
        $this->setFilePath($filePath);
        $this->setFileName($fileName);
        $this->setFileSize($fileSize);
        $this->setMimeType($mimeType);
        $this->setMetadata($metadata);
        $this->setProgress(100);
        
        return $this;
    }
}
