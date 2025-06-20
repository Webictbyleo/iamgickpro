<?php

declare(strict_types=1);

namespace App\Service\Plugin\Config;

use Symfony\Component\Yaml\Yaml;

/**
 * Plugin Configuration Loader
 * 
 * Loads plugin configurations from YAML files and creates PluginConfig objects
 */
class PluginConfigLoader
{
    public function __construct(
        private readonly string $projectDir
    ) {}

    /**
     * Load plugin configuration from YAML file
     */
    public function loadConfig(string $pluginId): PluginConfig
    {
        $configPath = $this->projectDir . '/config/plugins/' . $pluginId . '.yaml';
        
        if (!file_exists($configPath)) {
            throw new \RuntimeException(sprintf('Plugin config file not found: %s', $configPath));
        }

        $configData = Yaml::parseFile($configPath);
        
        if (!is_array($configData)) {
            throw new \RuntimeException(sprintf('Invalid plugin config file: %s', $configPath));
        }

        return $this->createPluginConfig($configData);
    }

    /**
     * Create PluginConfig object from array data
     */
    private function createPluginConfig(array $data): PluginConfig
    {
        // Validate required fields
        $requiredFields = ['name', 'description', 'version', 'type'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new \RuntimeException(sprintf('Missing required field in plugin config: %s', $field));
            }
        }

        // Create InternetConfig if present
        $internetConfig = null;
        if (isset($data['internet'])) {
            $internetData = $data['internet'];
            $internetConfig = new InternetConfig(
                required: $internetData['required'] ?? true,
                integrations: $internetData['integrations'] ?? [],
                endpoints: $internetData['endpoints'] ?? [],
                requiresAuth: $internetData['requires_auth'] ?? true,
                authType: $internetData['auth_type'] ?? 'api_key',
                permissions: $internetData['permissions'] ?? [],
                rateLimit: $internetData['rate_limit'] ?? [],
                metadata: $internetData['metadata'] ?? [],
                allowedDomains: $internetData['allowed_domains'] ?? [],
                blockedDomains: $internetData['blocked_domains'] ?? [],
                timeout: $internetData['timeout'] ?? null,
                maxRedirects: $internetData['max_redirects'] ?? null
            );
        }

        return new PluginConfig(
            id: $data['id'] ?? 'unknown',
            name: $data['name'],
            description: $data['description'],
            version: $data['version'],
            icon: $data['icon'] ?? '/icons/plugins/default.svg',
            type: PluginType::from($data['type']),
            supportedCommands: $data['commands'] ?? [],
            dependencies: $data['dependencies'] ?? [],
            internet: $internetConfig,
            requirements: $data['requirements'] ?? [],
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Check if plugin config file exists
     */
    public function configExists(string $pluginId): bool
    {
        $configPath = $this->projectDir . '/config/plugins/' . $pluginId . '.yaml';
        return file_exists($configPath);
    }

    /**
     * Get all available plugin configs
     */
    public function getAvailablePlugins(): array
    {
        $pluginDir = $this->projectDir . '/config/plugins';
        
        if (!is_dir($pluginDir)) {
            return [];
        }

        $plugins = [];
        $files = glob($pluginDir . '/*.yaml');
        
        foreach ($files as $file) {
            $pluginId = basename($file, '.yaml');
            try {
                $plugins[$pluginId] = $this->loadConfig($pluginId);
            } catch (\Exception $e) {
                // Log error but continue with other plugins
                error_log(sprintf('Failed to load plugin config %s: %s', $pluginId, $e->getMessage()));
            }
        }

        return $plugins;
    }
}
