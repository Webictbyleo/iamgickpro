<?php

declare(strict_types=1);

namespace App\Service\Plugin;

use App\DTO\Request\PluginCommandRequestDTO;
use App\Entity\Layer;
use App\Entity\Plugin;
use App\Entity\User;
use App\Repository\LayerRepository;
use App\Repository\PluginRepository;
use App\Service\Plugin\Plugins\PluginInterface;
use App\Service\Plugin\Plugins\AbstractPlugin;
use App\Service\Plugin\Plugins\RemoveBgPlugin;
use App\Service\Plugin\Plugins\YoutubeThumbnailPlugin;
use App\Service\Plugin\Config\PluginConfigLoader;
use App\Service\MediaProcessing\MediaProcessingService;
use App\Service\MediaProcessing\AsyncMediaProcessingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Plugin Service
 * 
 * Central service for managing plugin operations, command execution, and plugin lifecycle.
 * Handles plugin discovery, validation, and secure execution of plugin commands.
 */
class PluginService
{
    /** @var array<string, AbstractPlugin> */
    private array $plugins = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LayerRepository $layerRepository,
        private readonly PluginRepository $pluginRepository,
        private readonly SecureRequestBuilder $requestBuilder,
        private readonly MediaProcessingService $mediaProcessingService,
        private readonly PluginConfigLoader $configLoader,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
        private readonly CacheItemPoolInterface $cache,
        private readonly AsyncMediaProcessingService $asyncService,
        private readonly MessageBusInterface $messageBus,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%kernel.project_dir%/public/uploads/plugins')]
        private readonly string $pluginDirectory
    ) {
        // Built-in plugins are now loaded dynamically when needed
    }

    /**
     * Execute a plugin command
     */
    public function executeCommand(User $user, PluginCommandRequestDTO $dto): array
    {
        try {
            // Get plugin instance
            $plugin = $this->getPlugin($dto->pluginId);
            
            if (!$plugin) {
                throw new \RuntimeException(sprintf('Plugin not found: %s', $dto->pluginId));
            }

            // Validate command
            if (!$plugin->supportsCommand($dto->command)) {
                throw new \RuntimeException(sprintf('Command not supported by plugin %s: %s', $dto->pluginId, $dto->command));
            }

            // Validate layer access if plugin requires a layer
            $layer = null;
            if ($plugin->requiresLayer()) {
                if (!$dto->layerId) {
                    throw new \RuntimeException(sprintf('Layer ID is required for plugin %s', $dto->pluginId));
                }
                $layer = $this->validateLayerAccess($user, $dto->layerId);
            }

            // Execute command
            $result = $plugin->executeCommand($user, $layer, $dto->command, $dto->parameters, $dto->options);
            
            // Save any layer changes
            $this->entityManager->flush();
            
            $this->logger->info('Plugin command executed successfully', [
                'plugin' => $dto->pluginId,
                'command' => $dto->command,
                'layer_id' => $dto->layerId,
                'user_id' => $user->getId()
            ]);

            $response = [
                'success' => true,
                'result' => $result
            ];

            // Include layer info if layer was involved
            if ($layer) {
                $response['layer'] = [
                    'id' => $layer->getId(),
                    'plugins' => $layer->getPlugins()
                ];
            }

            return $response;
            
        } catch (\Exception $e) {
            $this->logger->error('Plugin command execution failed', [
                'plugin' => $dto->pluginId,
                'command' => $dto->command,
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get available plugins for a user
     */
    public function getAvailablePlugins(User $user): array
    {
        $availablePlugins = [];
        
        foreach ($this->plugins as $pluginId => $plugin) {
            if ($plugin->isAvailableForUser($user)) {
                $availablePlugins[] = [
                    'id' => $pluginId,
                    'name' => $plugin->getName(),
                    'description' => $plugin->getDescription(),
                    'icon' => $plugin->getIcon(),
                    'version' => $plugin->getVersion(),
                    'commands' => $plugin->getSupportedCommands(),
                    'requirements' => $plugin->getRequirements()
                ];
            }
        }
        
        return $availablePlugins;
    }

    /**
     * Get plugin instance by ID - loads dynamically if not already cached
     */
    public function getPlugin(string $pluginId): ?AbstractPlugin
    {
        // Return cached plugin if already loaded
        if (isset($this->plugins[$pluginId])) {
            return $this->plugins[$pluginId];
        }

        // Try to load built-in plugin dynamically
        $plugin = $this->loadBuiltInPlugin($pluginId);
        if ($plugin) {
            $this->plugins[$pluginId] = $plugin;
            return $plugin;
        }

        // Plugin not found
        return null;
    }

    /**
     * Load built-in plugin by ID
     */
    private function loadBuiltInPlugin(string $pluginId): ?AbstractPlugin
    {
        switch ($pluginId) {
            case 'removebg':
                return $this->createRemoveBgPlugin();
            
            case 'youtube_thumbnail':
                return $this->createYouTubeThumbnailPlugin();
            
            default:
                return null;
        }
    }

    /**
     * Create RemoveBG plugin instance
     */
    private function createRemoveBgPlugin(): RemoveBgPlugin
    {
        $plugin = new RemoveBgPlugin(
            $this->requestBuilder,
            $this,
            $this->requestStack,
            $this->logger,
            $this->cache,
            $this->environment,
            $this->projectDir
        );
        
        try {
            $config = $this->configLoader->loadConfig('remove_bg');
            $plugin->setConfig($config);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to load config for RemoveBG plugin, using defaults', [
                'error' => $e->getMessage()
            ]);
        }
        
        return $plugin;
    }

    /**
     * Create YouTube Thumbnail plugin instance
     */
    private function createYouTubeThumbnailPlugin(): YoutubeThumbnailPlugin
    {
        $plugin = new YoutubeThumbnailPlugin(
            $this->requestBuilder,
            $this,
            $this->mediaProcessingService,
            $this->asyncService,
            $this->messageBus,
            $this->requestStack,
            $this->logger,
            $this->cache,
            $this->environment,
            $this->projectDir
        );
        
        try {
            $config = $this->configLoader->loadConfig('youtube_thumbnail');
            $plugin->setConfig($config);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to load config for YouTube Thumbnail plugin, using defaults', [
                'error' => $e->getMessage()
            ]);
        }
        
        return $plugin;
    }

    /**
     * Register a plugin instance
     */
    public function registerPlugin(string $pluginId, AbstractPlugin $plugin): void
    {
        $this->plugins[$pluginId] = $plugin;
    }

    /**
     * Get plugin data for a layer
     */
    public function getLayerPluginData(Layer $layer, string $pluginId): ?array
    {
        return $layer->getPluginData($pluginId);
    }

    /**
     * Update plugin data for a layer
     */
    public function updateLayerPluginData(Layer $layer, string $pluginId, array $data): void
    {
        $layer->addPluginData($pluginId, $data);
        $this->entityManager->flush();
    }

    /**
     * Create plugin directory for file operations
     */
    public function getPluginDirectory(string $pluginId): string
    {
        $pluginDir = $this->pluginDirectory . '/' . $pluginId;
        if (!is_dir($pluginDir)) {
            mkdir($pluginDir, 0755, true);
        }
        return $pluginDir;
    }

    /**
     * Validate layer access for user
     */
    private function validateLayerAccess(User $user, int $layerId): Layer
    {
        $layer = $this->layerRepository->find($layerId);
        
        if (!$layer) {
            throw new \RuntimeException('Layer not found');
        }
        
        $design = $layer->getDesign();
        if (!$design) {
            throw new \RuntimeException('Layer design not found');
        }
        
        $project = $design->getProject();
        if (!$project || $project->getUser() !== $user) {
            throw new \RuntimeException('Access denied to layer');
        }
        
        return $layer;
    }
}
