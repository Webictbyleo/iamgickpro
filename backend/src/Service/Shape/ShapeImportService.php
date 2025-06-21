<?php

declare(strict_types=1);

namespace App\Service\Shape;

use App\Entity\Shape;
use App\Repository\ShapeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Shape Import Service
 * 
 * Handles importing SVG shapes from the design-vector-shapes repository
 * into the database for searchable stock media functionality.
 */
class ShapeImportService
{
    private const SHAPES_PATH = '/var/www/html/iamgickpro/backend/storage/shapes';
    private const MASTER_INDEX_PATH = self::SHAPES_PATH . '/indices/master_index.json';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ShapeRepository $shapeRepository,
        private readonly LoggerInterface $logger,
        private readonly Filesystem $filesystem
    ) {}

    /**
     * Import all shapes from the master index JSON file
     * 
     * @return array{imported: int, skipped: int, errors: int, duration: float}
     */
    public function importShapes(): array
    {
        $startTime = microtime(true);
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        $this->logger->info('Starting shape import process');

        try {
            if (!$this->filesystem->exists(self::MASTER_INDEX_PATH)) {
                throw new \RuntimeException('Master index file not found at: ' . self::MASTER_INDEX_PATH);
            }

            $masterIndexContent = file_get_contents(self::MASTER_INDEX_PATH);
            if ($masterIndexContent === false) {
                throw new \RuntimeException('Failed to read master index file');
            }

            $masterIndex = json_decode($masterIndexContent, true);
            if ($masterIndex === null) {
                throw new \RuntimeException('Failed to parse master index JSON: ' . json_last_error_msg());
            }

            if (!isset($masterIndex['items']) || !is_array($masterIndex['items'])) {
                throw new \RuntimeException('Invalid master index format: missing items array');
            }

            $this->logger->info('Master index loaded', [
                'total_files' => $masterIndex['total_files'] ?? 0,
                'categories' => count($masterIndex['categories'] ?? []),
                'items_count' => count($masterIndex['items'])
            ]);

            $shapesToImport = [];
            foreach ($masterIndex['items'] as $item) {
                try {
                    // Check if shape already exists
                    if ($this->shapeRepository->existsByPath($item['path'])) {
                        $skipped++;
                        continue;
                    }

                    // Validate required fields
                    $this->validateShapeItem($item);

                    // Normalize missing fields with defaults
                    $item = $this->normalizeShapeItem($item);

                    // Verify the actual SVG file exists
                    $absolutePath = self::SHAPES_PATH . '/' . $item['path'];
                    if (!$this->filesystem->exists($absolutePath)) {
                        $this->logger->warning('SVG file not found', [
                            'path' => $item['path'],
                            'absolute_path' => $absolutePath
                        ]);
                        $errors++;
                        continue;
                    }

                    // Create Shape entity
                    $shape = new Shape();
                    $shape->setOriginalFilename($item['original_filename'])
                          ->setNormalizedFilename($item['normalized_filename'])
                          ->setCategory($item['category'])
                          ->setPath($item['path'])
                          ->setOriginalPath($item['original_path'])
                          ->setKeywords($item['keywords'] ?? [])
                          ->setShapeCategory($item['shape_category'])
                          ->setDescription($item['description'])
                          ->setFileSize($item['file_size']);

                    $shapesToImport[] = $shape;

                    // Batch process every 50 shapes
                    if (count($shapesToImport) >= 50) {
                        $this->batchSaveShapes($shapesToImport);
                        $imported += count($shapesToImport);
                        $shapesToImport = [];
                        
                        $this->logger->info('Batch processed', ['imported_so_far' => $imported]);
                    }

                } catch (\Exception $e) {
                    $this->logger->error('Failed to process shape item', [
                        'item' => $item,
                        'error' => $e->getMessage()
                    ]);
                    $errors++;
                }
            }

            // Process remaining shapes
            if (!empty($shapesToImport)) {
                $this->batchSaveShapes($shapesToImport);
                $imported += count($shapesToImport);
            }

        } catch (\Exception $e) {
            $this->logger->error('Shape import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        $duration = microtime(true) - $startTime;

        $this->logger->info('Shape import completed', [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'duration' => $duration
        ]);

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'duration' => $duration
        ];
    }

    /**
     * Clear all existing shapes from the database
     */
    public function clearShapes(): int
    {
        $this->logger->info('Clearing all shapes from database');
        
        $count = $this->entityManager->createQuery('DELETE FROM App\Entity\Shape s')
                                    ->execute();
        
        $this->logger->info('Shapes cleared', ['count' => $count]);
        
        return $count;
    }

    /**
     * Re-import all shapes (clear existing and import fresh)
     * 
     * @return array{imported: int, skipped: int, errors: int, duration: float, cleared: int}
     */
    public function reimportShapes(): array
    {
        $cleared = $this->clearShapes();
        $result = $this->importShapes();
        $result['cleared'] = $cleared;
        
        return $result;
    }

    /**
     * Get import statistics
     */
    public function getImportStatistics(): array
    {
        $stats = $this->shapeRepository->getStatistics();
        
        // Add file system statistics
        if ($this->filesystem->exists(self::MASTER_INDEX_PATH)) {
            $masterIndexContent = file_get_contents(self::MASTER_INDEX_PATH);
            $masterIndex = json_decode($masterIndexContent, true);
            
            $stats['available_files'] = $masterIndex['total_files'] ?? 0;
            $stats['available_categories'] = count($masterIndex['categories'] ?? []);
        }
        
        return $stats;
    }

    /**
     * Validate required fields in shape item
     */
    private function validateShapeItem(array $item): void
    {
        $requiredFields = [
            'original_filename',
            'normalized_filename',
            'category',
            'path',
            'original_path',
            'file_size'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($item[$field]) || (is_string($item[$field]) && trim($item[$field]) === '')) {
                throw new \InvalidArgumentException("Required field '{$field}' is missing or empty");
            }
        }

        // Validate numeric fields
        if (!is_int($item['file_size']) || $item['file_size'] < 0) {
            throw new \InvalidArgumentException('file_size must be a positive integer');
        }

        // Validate arrays
        if (isset($item['keywords']) && !is_array($item['keywords'])) {
            throw new \InvalidArgumentException('keywords must be an array');
        }
    }

    /**
     * Normalize shape item with default values for missing fields
     */
    private function normalizeShapeItem(array $item): array
    {
        // Provide default values for missing fields
        $item['keywords'] = $item['keywords'] ?? [];
        $item['shape_category'] = $item['shape_category'] ?? 'general';
        $item['description'] = $item['description'] ?? 'SVG shape from ' . ($item['category'] ?? 'unknown') . ' category';
        
        // Ensure empty strings are replaced with defaults
        if (empty(trim($item['shape_category']))) {
            $item['shape_category'] = 'general';
        }
        
        if (empty(trim($item['description']))) {
            $item['description'] = 'SVG shape from ' . ($item['category'] ?? 'unknown') . ' category';
        }
        
        return $item;
    }

    /**
     * Batch save shapes for better performance
     * 
     * @param Shape[] $shapes
     */
    private function batchSaveShapes(array $shapes): void
    {
        foreach ($shapes as $shape) {
            $this->entityManager->persist($shape);
        }
        
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    /**
     * Check if shapes data is available
     */
    public function isShapesDataAvailable(): bool
    {
        return $this->filesystem->exists(self::MASTER_INDEX_PATH) &&
               $this->filesystem->exists(self::SHAPES_PATH . '/normalized');
    }

    /**
     * Get information about available shapes data
     */
    public function getShapesDataInfo(): array
    {
        if (!$this->isShapesDataAvailable()) {
            return ['available' => false];
        }

        $masterIndexContent = file_get_contents(self::MASTER_INDEX_PATH);
        $masterIndex = json_decode($masterIndexContent, true);

        return [
            'available' => true,
            'total_files' => $masterIndex['total_files'] ?? 0,
            'categories' => $masterIndex['categories'] ?? [],
            'generated_at' => $masterIndex['generated_at'] ?? null,
            'path' => self::SHAPES_PATH
        ];
    }
}
