<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\UserIntegration;
use App\Repository\UserIntegrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Integration Service
 * 
 * Handles third-party API credentials encryption, storage, and retrieval.
 * Supports various services like OpenAI, Remove.bg, Unsplash, Pexels, and Replicate.
 */
readonly class IntegrationService
{
    private const ENCRYPTION_CIPHER = 'AES-256-CBC';
    
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserIntegrationRepository $integrationRepository,
        private LoggerInterface $logger,
        private string $encryptionKey
    ) {}

    /**
     * Save encrypted API credentials for a user
     */
    public function saveCredentials(User $user, string $serviceName, array $credentials, array $settings = []): UserIntegration
    {
        try {
            // Find existing integration or create new one
            $integration = $this->integrationRepository->findOneBy([
                'user' => $user,
                'serviceName' => $serviceName
            ]);

            if (!$integration) {
                $integration = new UserIntegration();
                $integration->setUser($user);
                $integration->setServiceName($serviceName);
                $this->entityManager->persist($integration);
            }

            // Encrypt credentials
            $encryptedCredentials = $this->encryptData(json_encode($credentials));
            $integration->setEncryptedCredentials($encryptedCredentials);
            $integration->setSettings($settings);

            $this->entityManager->flush();

            $this->logger->info('Integration credentials saved', [
                'user_id' => $user->getId(),
                'service' => $serviceName
            ]);

            return $integration;
        } catch (\Exception $e) {
            $this->logger->error('Failed to save integration credentials', [
                'user_id' => $user->getId(),
                'service' => $serviceName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get decrypted API credentials for a user
     */
    public function getCredentials(User $user, string $serviceName): ?array
    {
        try {
            $integration = $this->integrationRepository->findOneBy([
                'user' => $user,
                'serviceName' => $serviceName
            ]);

            if (!$integration) {
                return null;
            }

            // Decrypt credentials
            $decryptedData = $this->decryptData($integration->getEncryptedCredentials());
            return json_decode($decryptedData, true);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get integration credentials', [
                'user_id' => $user->getId(),
                'service' => $serviceName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get integration settings
     */
    public function getSettings(User $user, string $serviceName): ?array
    {
        $integration = $this->integrationRepository->findOneBy([
            'user' => $user,
            'serviceName' => $serviceName
        ]);

        return $integration?->getSettings();
    }

    /**
     * Get all user integrations
     */
    public function getUserIntegrations(User $user): array
    {
        $integrations = $this->integrationRepository->findBy(['user' => $user]);
        $result = [];

        foreach ($integrations as $integration) {
            $result[] = [
                'service' => $integration->getServiceName(),
                'isConfigured' => !empty($integration->getEncryptedCredentials()),
                'settings' => $integration->getSettings(),
                'createdAt' => $integration->getCreatedAt(),
                'updatedAt' => $integration->getUpdatedAt()
            ];
        }

        return $result;
    }

    /**
     * Remove integration
     */
    public function removeIntegration(User $user, string $serviceName): bool
    {
        try {
            $integration = $this->integrationRepository->findOneBy([
                'user' => $user,
                'serviceName' => $serviceName
            ]);

            if (!$integration) {
                return false;
            }

            $this->entityManager->remove($integration);
            $this->entityManager->flush();

            $this->logger->info('Integration removed', [
                'user_id' => $user->getId(),
                'service' => $serviceName
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to remove integration', [
                'user_id' => $user->getId(),
                'service' => $serviceName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Test API credentials by making a simple API call
     */
    public function testCredentials(User $user, string $serviceName, ?array $credentials = null): array
    {
        try {
            // Use provided credentials or get from storage
            if ($credentials === null) {
                $credentials = $this->getCredentials($user, $serviceName);
                if (!$credentials) {
                    return ['success' => false, 'error' => 'No credentials found'];
                }
            }

            $result = match ($serviceName) {
                'openai' => $this->testOpenAI($credentials),
                'removebg' => $this->testRemoveBg($credentials),
                'unsplash' => $this->testUnsplash($credentials),
                'pexels' => $this->testPexels($credentials),
                'replicate' => $this->testReplicate($credentials),
                default => ['success' => false, 'error' => 'Unsupported service']
            };

            $this->logger->info('API credentials tested', [
                'user_id' => $user->getId(),
                'service' => $serviceName,
                'success' => $result['success']
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to test API credentials', [
                'user_id' => $user->getId(),
                'service' => $serviceName,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Encrypt sensitive data
     */
    private function encryptData(string $data): string
    {
        $iv = random_bytes(openssl_cipher_iv_length(self::ENCRYPTION_CIPHER));
        $encrypted = openssl_encrypt($data, self::ENCRYPTION_CIPHER, $this->encryptionKey, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt sensitive data
     */
    private function decryptData(string $encryptedData): string
    {
        $data = base64_decode($encryptedData);
        $ivLength = openssl_cipher_iv_length(self::ENCRYPTION_CIPHER);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt($encrypted, self::ENCRYPTION_CIPHER, $this->encryptionKey, 0, $iv);
    }

    /**
     * Test OpenAI API credentials
     */
    private function testOpenAI(array $credentials): array
    {
        // Simple test - validate API key format
        $apiKey = $credentials['api_key'] ?? '';
        if (empty($apiKey) || !str_starts_with($apiKey, 'sk-')) {
            return ['success' => false, 'error' => 'Invalid API key format'];
        }

        // In a real implementation, you would make a simple API call
        // For now, we'll just validate the format
        return ['success' => true, 'message' => 'API key format is valid'];
    }

    /**
     * Test Remove.bg API credentials
     */
    private function testRemoveBg(array $credentials): array
    {
        $apiKey = $credentials['api_key'] ?? '';
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'API key is required'];
        }

        // Basic validation - Remove.bg API keys are typically alphanumeric
        if (!preg_match('/^[a-zA-Z0-9]+$/', $apiKey)) {
            return ['success' => false, 'error' => 'Invalid API key format'];
        }

        return ['success' => true, 'message' => 'API key format is valid'];
    }

    /**
     * Test Unsplash API credentials
     */
    private function testUnsplash(array $credentials): array
    {
        $accessKey = $credentials['access_key'] ?? '';
        if (empty($accessKey)) {
            return ['success' => false, 'error' => 'Access key is required'];
        }

        // Basic validation for Unsplash access keys
        if (strlen($accessKey) < 20) {
            return ['success' => false, 'error' => 'Invalid access key format'];
        }

        return ['success' => true, 'message' => 'Access key format is valid'];
    }

    /**
     * Test Pexels API credentials
     */
    private function testPexels(array $credentials): array
    {
        $apiKey = $credentials['api_key'] ?? '';
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'API key is required'];
        }

        // Basic validation for Pexels API keys
        if (strlen($apiKey) < 20) {
            return ['success' => false, 'error' => 'Invalid API key format'];
        }

        return ['success' => true, 'message' => 'API key format is valid'];
    }

    /**
     * Test Replicate API credentials
     */
    private function testReplicate(array $credentials): array
    {
        $apiKey = $credentials['api_key'] ?? '';
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'API key is required'];
        }

        // Basic validation for Replicate API keys (they start with 'r8_')
        if (!str_starts_with($apiKey, 'r8_')) {
            return ['success' => false, 'error' => 'Invalid Replicate API key format - should start with r8_'];
        }

        // Additional length validation
        if (strlen($apiKey) < 30) {
            return ['success' => false, 'error' => 'Invalid API key format - too short'];
        }

        return ['success' => true, 'message' => 'Replicate API key format is valid'];
    }
}
