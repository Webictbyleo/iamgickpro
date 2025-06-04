<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'App\Repository\VideoAnalysisRepository')]
#[ORM\Table(name: 'video_analysis')]
#[ORM\Index(columns: ['status'], name: 'idx_video_analysis_status')]
#[ORM\Index(columns: ['created_at'], name: 'idx_video_analysis_created')]
#[ORM\Index(columns: ['user_id', 'status'], name: 'idx_user_video_analysis_status')]
#[ORM\Index(columns: ['video_id'], name: 'idx_video_analysis_video_id')]
class VideoAnalysis
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STYLE_PROFESSIONAL = 'professional';
    public const STYLE_CREATIVE = 'creative';
    public const STYLE_GAMING = 'gaming';
    public const STYLE_LIFESTYLE = 'lifestyle';
    public const STYLE_TECH = 'tech';
    public const STYLE_EDUCATIONAL = 'educational';

    public const SIZE_STANDARD = '1280x720';
    public const SIZE_HD = '1920x1080';
    public const SIZE_SQUARE = '1080x1080';
    public const SIZE_VERTICAL = '1080x1920';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['video_analysis:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['video_analysis:read'])]
    private Uuid $uuid;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['video_analysis:read'])]
    private readonly User $user;

    #[ORM\Column(type: Types::STRING, length: 500)]
    #[Assert\NotBlank]
    #[Assert\Url]
    #[Groups(['video_analysis:read'])]
    private readonly string $videoUrl;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank]
    #[Groups(['video_analysis:read'])]
    private readonly string $videoId;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\Choice(choices: [
        self::STYLE_PROFESSIONAL,
        self::STYLE_CREATIVE,
        self::STYLE_GAMING,
        self::STYLE_LIFESTYLE,
        self::STYLE_TECH,
        self::STYLE_EDUCATIONAL
    ])]
    #[Groups(['video_analysis:read'])]
    private readonly string $style;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\Choice(choices: [
        self::SIZE_STANDARD,
        self::SIZE_HD,
        self::SIZE_SQUARE,
        self::SIZE_VERTICAL
    ])]
    #[Groups(['video_analysis:read'])]
    private readonly string $size;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Range(min: 1, max: 10)]
    #[Groups(['video_analysis:read'])]
    private readonly int $maxThumbnails;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['video_analysis:read'])]
    private readonly ?string $customPrompt;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['video_analysis:read'])]
    private readonly ?array $designTypes;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\Choice(choices: [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED
    ])]
    #[Groups(['video_analysis:read'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['video_analysis:read'])]
    private int $progress = 0;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['video_analysis:read'])]
    private ?array $videoInfo = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['video_analysis:read'])]
    private ?array $extractedFrames = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['video_analysis:read'])]
    private ?string $transcript = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['video_analysis:read'])]
    private ?array $keyMoments = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['video_analysis:read'])]
    private ?array $suggestedDesigns = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['video_analysis:read'])]
    private ?array $colorPalette = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['video_analysis:read'])]
    private ?array $dominantThemes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['video_analysis:read'])]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['video_analysis:read'])]
    private ?array $errorDetails = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['video_analysis:read'])]
    private ?int $processingTimeMs = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['video_analysis:read'])]
    private ?int $estimatedTime = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['video_analysis:read'])]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['video_analysis:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['video_analysis:read'])]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['video_analysis:read'])]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['video_analysis:read'])]
    private ?\DateTimeImmutable $expiresAt = null;

    public function __construct(
        User $user,
        string $videoUrl,
        string $videoId,
        string $style = self::STYLE_PROFESSIONAL,
        string $size = self::SIZE_STANDARD,
        int $maxThumbnails = 5,
        ?string $customPrompt = null,
        ?array $designTypes = null
    ) {
        $this->uuid = Uuid::v4();
        $this->user = $user;
        $this->videoUrl = $videoUrl;
        $this->videoId = $videoId;
        $this->style = $style;
        $this->size = $size;
        $this->maxThumbnails = $maxThumbnails;
        $this->customPrompt = $customPrompt;
        $this->designTypes = $designTypes ?? ['thumbnail'];
        $this->createdAt = new \DateTimeImmutable();
        
        // Video analysis results expire after 7 days
        $this->expiresAt = $this->createdAt->modify('+7 days');
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

    public function getVideoUrl(): string
    {
        return $this->videoUrl;
    }

    public function getVideoId(): string
    {
        return $this->videoId;
    }

    public function getStyle(): string
    {
        return $this->style;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function getMaxThumbnails(): int
    {
        return $this->maxThumbnails;
    }

    public function getCustomPrompt(): ?string
    {
        return $this->customPrompt;
    }

    public function getDesignTypes(): ?array
    {
        return $this->designTypes;
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
                $processingTime = $this->completedAt->getTimestamp() - $this->startedAt->getTimestamp();
                $this->processingTimeMs = $processingTime * 1000;
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

    public function getVideoInfo(): ?array
    {
        return $this->videoInfo;
    }

    public function setVideoInfo(?array $videoInfo): self
    {
        $this->videoInfo = $videoInfo;
        return $this;
    }

    public function getExtractedFrames(): ?array
    {
        return $this->extractedFrames;
    }

    public function setExtractedFrames(?array $extractedFrames): self
    {
        $this->extractedFrames = $extractedFrames;
        return $this;
    }

    public function getTranscript(): ?string
    {
        return $this->transcript;
    }

    public function setTranscript(?string $transcript): self
    {
        $this->transcript = $transcript;
        return $this;
    }

    public function getKeyMoments(): ?array
    {
        return $this->keyMoments;
    }

    public function setKeyMoments(?array $keyMoments): self
    {
        $this->keyMoments = $keyMoments;
        return $this;
    }

    public function getSuggestedDesigns(): ?array
    {
        return $this->suggestedDesigns;
    }

    public function setSuggestedDesigns(?array $suggestedDesigns): self
    {
        $this->suggestedDesigns = $suggestedDesigns;
        return $this;
    }

    public function getColorPalette(): ?array
    {
        return $this->colorPalette;
    }

    public function setColorPalette(?array $colorPalette): self
    {
        $this->colorPalette = $colorPalette;
        return $this;
    }

    public function getDominantThemes(): ?array
    {
        return $this->dominantThemes;
    }

    public function setDominantThemes(?array $dominantThemes): self
    {
        $this->dominantThemes = $dominantThemes;
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

    public function getEstimatedTime(): ?int
    {
        return $this->estimatedTime;
    }

    public function setEstimatedTime(?int $estimatedTime): self
    {
        $this->estimatedTime = $estimatedTime;
        return $this;
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
        return $this->expiresAt !== null && $this->expiresAt < new \DateTimeImmutable();
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isProcessing(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function getJobId(): string
    {
        return $this->uuid->toRfc4122();
    }

    public function getAnalysisResult(): ?array
    {
        if (!$this->isCompleted()) {
            return null;
        }

        return [
            'videoInfo' => $this->videoInfo,
            'extractedFrames' => $this->extractedFrames,
            'transcript' => $this->transcript,
            'keyMoments' => $this->keyMoments,
            'suggestedDesigns' => $this->suggestedDesigns,
            'colorPalette' => $this->colorPalette,
            'dominantThemes' => $this->dominantThemes,
        ];
    }
}
