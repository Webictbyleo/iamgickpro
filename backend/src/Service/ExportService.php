<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Design;
use App\Entity\ExportJob;
use App\Entity\User;
use App\Repository\ExportJobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing export jobs and operations
 */
class ExportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ExportJobRepository $exportJobRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly string $exportDirectory,
    ) {
        // Ensure export directory exists
        $this->ensureDirectoryExists($this->exportDirectory);
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }
        }
    }

    /**
     * Create a new export job
     */
    public function createExportJob(
        Design $design,
        User $user,
        string $format,
        array $options = []
    ): ExportJob {
        $validFormats = ['png', 'jpg', 'jpeg', 'svg', 'pdf', 'gif', 'mp4', 'webm'];
        
        if (!in_array(strtolower($format), $validFormats)) {
            throw new \InvalidArgumentException('Invalid export format: ' . $format);
        }

        $quality = $options['quality'] ?? ExportJob::QUALITY_MEDIUM;
        $width = $options['width'] ?? null;
        $height = $options['height'] ?? null;
        $scale = $options['scale'] ?? null;
        $transparent = $options['transparent'] ?? false;
        $backgroundColor = $options['backgroundColor'] ?? null;
        $animationSettings = $options['animationSettings'] ?? null;

        $exportJob = new ExportJob(
            $user,
            $design,
            strtolower($format),
            $quality,
            $width,
            $height,
            $scale,
            $transparent,
            $backgroundColor,
            $animationSettings
        );

        $this->entityManager->persist($exportJob);
        $this->entityManager->flush();

        // Queue the export job for processing
        $this->queueExportJob($exportJob);

        return $exportJob;
    }

    /**
     * Queue export job for background processing
     */
    private function queueExportJob(ExportJob $exportJob): void
    {
        // This would dispatch a message to the message bus
        // For now, we'll just update the status
        $exportJob->setStatus('queued');
        $this->entityManager->flush();
        
        // TODO: Implement actual message dispatch
        // $this->messageBus->dispatch(new ProcessExportJobMessage($exportJob->getId()));
    }

    /**
     * Process export job (this would be called by a message handler)
     */
    public function processExportJob(ExportJob $exportJob): void
    {
        try {
            $exportJob->setStatus('processing');
            $this->entityManager->flush();

            // Generate the export based on format
            $outputPath = $this->generateExport($exportJob);
            
            // Get file information
            $fileName = basename($outputPath);
            $fileSize = file_exists($outputPath) ? filesize($outputPath) : 0;
            $mimeType = $this->getMimeType($exportJob->getFormat());

            $exportJob->markAsCompleted($outputPath, $fileName, $fileSize, $mimeType);
            $this->entityManager->flush();

        } catch (\Exception $e) {
            $exportJob->markAsFailed($e->getMessage());
            $this->entityManager->flush();
            
            throw $e;
        }
    }

    /**
     * Generate export file based on format
     */
    private function generateExport(ExportJob $exportJob): string
    {
        $design = $exportJob->getDesign();
        $format = $exportJob->getFormat();

        // Use configured export directory
        $exportDir = $this->exportDirectory;

        $filename = sprintf(
            'design_%d_%s_%s.%s',
            $design->getId(),
            $exportJob->getId(),
            date('Ymd_His'),
            $format
        );
        
        $outputPath = $exportDir . '/' . $filename;

        // Generate SVG first (this is the base format)
        $svgContent = $this->generateSVG($design, $exportJob);
        
        if ($format === 'svg') {
            file_put_contents($outputPath, $svgContent);
            return $outputPath;
        }

        // For other formats, convert from SVG
        return $this->convertFromSVG($svgContent, $outputPath, $format, $exportJob);
    }

    /**
     * Get MIME type for format
     */
    private function getMimeType(string $format): string
    {
        return match ($format) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'gif' => 'image/gif',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }

    /**
     * Generate SVG from design
     */
    private function generateSVG(Design $design, ExportJob $exportJob): string
    {
        $width = $design->getCanvasWidth();
        $height = $design->getCanvasHeight();
        $background = $design->getBackground();
        
        // Start SVG document
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">',
            $width, $height, $width, $height
        );

        // Add background
        if (isset($background['type']) && $background['type'] === 'color') {
            $color = $background['color'] ?? '#ffffff';
            $svg .= sprintf('<rect width="100%%" height="100%%" fill="%s"/>', $color);
        }

        // Add layers (ordered by z-index)
        $layers = $design->getLayers()->toArray();
        usort($layers, fn($a, $b) => $a->getZIndex() <=> $b->getZIndex());

        foreach ($layers as $layer) {
            if (!$layer->isVisible()) {
                continue;
            }
            
            $svg .= $this->generateLayerSVG($layer);
        }

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Generate SVG for a single layer
     */
    private function generateLayerSVG($layer): string
    {
        $type = $layer->getType();
        $properties = $layer->getProperties();
        
        $transform = sprintf(
            'translate(%f, %f) rotate(%f) scale(%f, %f)',
            $layer->getX(),
            $layer->getY(),
            $layer->getRotation(),
            $layer->getScaleX(),
            $layer->getScaleY()
        );

        switch ($type) {
            case 'text':
                return $this->generateTextSVG($layer, $properties, $transform);
            case 'image':
                return $this->generateImageSVG($layer, $properties, $transform);
            case 'shape':
                return $this->generateShapeSVG($layer, $properties, $transform);
            default:
                return '';
        }
    }

    /**
     * Generate SVG for text layer
     */
    private function generateTextSVG($layer, array $properties, string $transform): string
    {
        $text = $properties['text'] ?? '';
        $fontSize = $properties['fontSize'] ?? 16;
        $color = $properties['color'] ?? '#000000';
        $fontFamily = $properties['fontFamily'] ?? 'Arial';
        
        return sprintf(
            '<text x="0" y="%f" font-family="%s" font-size="%f" fill="%s" opacity="%f" transform="%s">%s</text>',
            $fontSize, // Approximate baseline offset
            htmlspecialchars($fontFamily),
            $fontSize,
            $color,
            $layer->getOpacity(),
            $transform,
            htmlspecialchars($text)
        );
    }

    /**
     * Generate SVG for image layer
     */
    private function generateImageSVG($layer, array $properties, string $transform): string
    {
        $src = $properties['src'] ?? '';
        
        return sprintf(
            '<image x="0" y="0" width="%f" height="%f" href="%s" opacity="%f" transform="%s"/>',
            $layer->getWidth(),
            $layer->getHeight(),
            htmlspecialchars($src),
            $layer->getOpacity(),
            $transform
        );
    }

    /**
     * Generate SVG for shape layer
     */
    private function generateShapeSVG($layer, array $properties, string $transform): string
    {
        $shapeType = $properties['shapeType'] ?? 'rectangle';
        $fill = $properties['fill'] ?? '#000000';
        $stroke = $properties['stroke'] ?? null;
        $strokeWidth = $properties['strokeWidth'] ?? 0;

        $style = sprintf('fill: %s; opacity: %f;', $fill, $layer->getOpacity());
        if ($stroke && $strokeWidth > 0) {
            $style .= sprintf(' stroke: %s; stroke-width: %f;', $stroke, $strokeWidth);
        }

        switch ($shapeType) {
            case 'rectangle':
                return sprintf(
                    '<rect x="0" y="0" width="%f" height="%f" style="%s" transform="%s"/>',
                    $layer->getWidth(),
                    $layer->getHeight(),
                    $style,
                    $transform
                );
            case 'circle':
                $radius = min($layer->getWidth(), $layer->getHeight()) / 2;
                return sprintf(
                    '<circle cx="%f" cy="%f" r="%f" style="%s" transform="%s"/>',
                    $layer->getWidth() / 2,
                    $layer->getHeight() / 2,
                    $radius,
                    $style,
                    $transform
                );
            default:
                return '';
        }
    }

    /**
     * Convert SVG to other formats using ImageMagick
     */
    private function convertFromSVG(string $svgContent, string $outputPath, string $format, ExportJob $exportJob): string
    {
        // Save SVG to temporary file first
        $tempSvgPath = tempnam(sys_get_temp_dir(), 'export_') . '.svg';
        file_put_contents($tempSvgPath, $svgContent);

        try {
            $command = $this->buildConversionCommand($tempSvgPath, $outputPath, $format, $exportJob);
            
            $result = shell_exec($command . ' 2>&1');
            
            if (!file_exists($outputPath)) {
                throw new \RuntimeException('Export conversion failed: ' . $result);
            }

            return $outputPath;
            
        } finally {
            // Clean up temporary file
            if (file_exists($tempSvgPath)) {
                unlink($tempSvgPath);
            }
        }
    }

    /**
     * Build ImageMagick conversion command
     */
    private function buildConversionCommand(string $inputPath, string $outputPath, string $format, ExportJob $exportJob): string
    {
        $command = 'convert';
        
        // Add format-specific options
        switch ($format) {
            case 'png':
                $command .= ' -background transparent';
                break;
            case 'jpg':
            case 'jpeg':
                $command .= ' -background white -flatten';
                $quality = $exportJob->getQuality() === 'high' ? 95 : ($exportJob->getQuality() === 'low' ? 60 : 80);
                $command .= sprintf(' -quality %d', $quality);
                break;
            case 'gif':
                $command .= ' -background transparent';
                break;
        }

        // Add resize if specified
        if ($exportJob->getWidth() || $exportJob->getHeight()) {
            $width = $exportJob->getWidth() ?? '';
            $height = $exportJob->getHeight() ?? '';
            $command .= sprintf(' -resize %sx%s', $width, $height);
        }

        $command .= sprintf(' "%s" "%s"', $inputPath, $outputPath);

        return $command;
    }

    /**
     * Retry failed export job
     */
    public function retryExportJob(ExportJob $exportJob): ExportJob
    {
        if ($exportJob->getStatus() !== 'failed') {
            throw new \InvalidArgumentException('Only failed export jobs can be retried');
        }

        // Create a new export job with the same parameters
        $newExportJob = new ExportJob(
            $exportJob->getUser(),
            $exportJob->getDesign(),
            $exportJob->getFormat(),
            $exportJob->getQuality(),
            $exportJob->getWidth(),
            $exportJob->getHeight(),
            $exportJob->getScale(),
            $exportJob->isTransparent(),
            $exportJob->getBackgroundColor(),
            $exportJob->getAnimationSettings()
        );

        $this->entityManager->persist($newExportJob);
        $this->entityManager->flush();

        $this->queueExportJob($newExportJob);

        return $newExportJob;
    }

    /**
     * Cancel pending export job
     */
    public function cancelExportJob(ExportJob $exportJob): ExportJob
    {
        $exportJob->cancel();
        $this->entityManager->flush();

        return $exportJob;
    }

    /**
     * Get download URL for completed export
     */
    public function getDownloadUrl(ExportJob $exportJob): string
    {
        if ($exportJob->getStatus() !== 'completed') {
            throw new \InvalidArgumentException('Export job is not completed');
        }

        $outputPath = $exportJob->getFilePath();
        if (!$outputPath || !file_exists($outputPath)) {
            throw new \RuntimeException('Export file not found');
        }

        // Return URL relative to web root
        $filename = $exportJob->getFileName();
        return sprintf('/api/exports/%d/download/%s', $exportJob->getId(), $filename);
    }

    /**
     * Clean up old export files
     */
    public function cleanupOldExports(int $daysOld = 30): int
    {
        $threshold = new \DateTimeImmutable(sprintf('-%d days', $daysOld));
        
        $oldExports = $this->exportJobRepository->createQueryBuilder('ej')
            ->where('ej.completedAt < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($oldExports as $export) {
            $outputPath = $export->getOutputPath();
            if ($outputPath && file_exists($outputPath)) {
                unlink($outputPath);
                $count++;
            }
            
            $this->entityManager->remove($export);
        }

        $this->entityManager->flush();

        return $count;
    }

    /**
     * Get export statistics for a user
     */
    public function getUserExportStats(User $user): array
    {
        return $this->exportJobRepository->getUserStats($user);
    }

    /**
     * Get system-wide export statistics
     */
    public function getSystemExportStats(): array
    {
        return $this->exportJobRepository->getQueueStats();
    }

    public function markJobAsFailed(ExportJob $exportJob, string $errorMessage): void
    {
        $exportJob->setStatus('failed');
        $exportJob->setErrorMessage($errorMessage);
        $exportJob->setCompletedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();

        $this->logger->error('Export job marked as failed', [
            'export_job_id' => $exportJob->getId(),
            'error' => $errorMessage
        ]);
    }
}
