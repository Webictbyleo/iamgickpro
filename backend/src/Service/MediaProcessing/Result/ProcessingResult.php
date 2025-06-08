<?php

declare(strict_types=1);

namespace App\Service\MediaProcessing\Result;

/**
 * Represents the result of a media processing operation
 */
readonly class ProcessingResult
{
    public function __construct(
        private bool $success,
        private ?string $outputPath = null,
        private ?string $errorMessage = null,
        private array $metadata = [],
        private array $processedFiles = [],
        private float $processingTime = 0.0,
        private ?string $jobId = null
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getOutputPath(): ?string
    {
        return $this->outputPath;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getProcessedFiles(): array
    {
        return $this->processedFiles;
    }

    public function getProcessingTime(): float
    {
        return $this->processingTime;
    }

    public function getJobId(): ?string
    {
        return $this->jobId;
    }

    public function hasMetadata(string $key): bool
    {
        return array_key_exists($key, $this->metadata);
    }

    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public static function success(
        string $outputPath,
        array $metadata = [],
        array $processedFiles = [],
        float $processingTime = 0.0,
        ?string $jobId = null
    ): self {
        return new self(
            success: true,
            outputPath: $outputPath,
            metadata: $metadata,
            processedFiles: $processedFiles,
            processingTime: $processingTime,
            jobId: $jobId
        );
    }

    public static function failure(
        string $errorMessage,
        array $metadata = [],
        float $processingTime = 0.0,
        ?string $jobId = null
    ): self {
        return new self(
            success: false,
            errorMessage: $errorMessage,
            metadata: $metadata,
            processingTime: $processingTime,
            jobId: $jobId
        );
    }

    public static function async(string $jobId, array $metadata = []): self
    {
        return new self(
            success: true,
            metadata: $metadata,
            jobId: $jobId
        );
    }
}
