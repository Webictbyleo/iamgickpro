<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Design;
use App\Entity\ExportJob;
use App\Entity\User;
use App\Repository\ExportJobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Service for managing export jobs and operations
 */
class ExportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ExportJobRepository $exportJobRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
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
            $exportJob->setStatus('processing')
                      ->setStartedAt(new \DateTimeImmutable());
            
            $this->entityManager->flush();

            $startTime = microtime(true);
            
            // Generate the export based on format
            $outputPath = $this->generateExport($exportJob);
            
            $endTime = microtime(true);
            $processingTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

            $exportJob->setStatus('completed')
                      ->setCompletedAt(new \DateTimeImmutable())
                      ->setOutputPath($outputPath)
                      ->setProcessingTimeMs((int) $processingTime);

            // Calculate file size if file exists
            if ($outputPath && file_exists($outputPath)) {
                $exportJob->setFileSize(filesize($outputPath));
            }

            $this->entityManager->flush();

        } catch (\Exception $e) {
            $exportJob->setStatus('failed')
                      ->setErrorMessage($e->getMessage())
                      ->setCompletedAt(new \DateTimeImmutable());
            
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
        $options = $exportJob->getOptions();

        // Create export directory if it doesn't exist
        $exportDir = '/var/www/html/iamgickpro/storage/exports';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $filename = sprintf(
            'design_%d_%s_%s.%s',
            $design->getId(),
            $exportJob->getId(),
            date('Ymd_His'),
            $format
        );
        
        $outputPath = $exportDir . '/' . $filename;

        // Generate SVG first (this is the base format)
        $svgContent = $this->generateSVG($design, $options);
        
        if ($format === 'svg') {
            file_put_contents($outputPath, $svgContent);
            return $outputPath;
        }

        // For other formats, convert from SVG
        return $this->convertFromSVG($svgContent, $outputPath, $format, $options);
    }

    /**
     * Generate SVG from design
     */
    private function generateSVG(Design $design, array $options = []): string
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
    private function convertFromSVG(string $svgContent, string $outputPath, string $format, array $options = []): string
    {
        // Save SVG to temporary file first
        $tempSvgPath = tempnam(sys_get_temp_dir(), 'export_') . '.svg';
        file_put_contents($tempSvgPath, $svgContent);

        try {
            $command = $this->buildConversionCommand($tempSvgPath, $outputPath, $format, $options);
            
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
    private function buildConversionCommand(string $inputPath, string $outputPath, string $format, array $options): string
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
                $quality = $options['quality'] ?? 90;
                $command .= sprintf(' -quality %d', $quality);
                break;
            case 'gif':
                $command .= ' -background transparent';
                break;
        }

        // Add DPI if specified
        if (isset($options['dpi'])) {
            $command .= sprintf(' -density %d', $options['dpi']);
        }

        // Add resize if specified
        if (isset($options['width']) || isset($options['height'])) {
            $width = $options['width'] ?? '';
            $height = $options['height'] ?? '';
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

        $retryCount = $exportJob->getRetryCount() + 1;
        
        if ($retryCount > 3) {
            throw new \InvalidArgumentException('Maximum retry count exceeded');
        }

        $exportJob->setStatus('pending')
                  ->setRetryCount($retryCount)
                  ->setErrorMessage(null)
                  ->setStartedAt(null)
                  ->setCompletedAt(null);

        $this->entityManager->flush();

        $this->queueExportJob($exportJob);

        return $exportJob;
    }

    /**
     * Cancel pending export job
     */
    public function cancelExportJob(ExportJob $exportJob): ExportJob
    {
        if (!in_array($exportJob->getStatus(), ['pending', 'queued'])) {
            throw new \InvalidArgumentException('Only pending or queued export jobs can be cancelled');
        }

        $exportJob->setStatus('cancelled')
                  ->setCompletedAt(new \DateTimeImmutable());

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

        $outputPath = $exportJob->getOutputPath();
        if (!$outputPath || !file_exists($outputPath)) {
            throw new \RuntimeException('Export file not found');
        }

        // Return URL relative to web root
        $filename = basename($outputPath);
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
}
