<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\AuthResponseDTO;
use App\DTO\Response\DesignResponseDTO;
use App\DTO\Response\ErrorResponseDTO;
use App\DTO\Response\ExportJobResponseDTO;
use App\DTO\Response\LayerResponseDTO;
use App\DTO\Response\MediaResponseDTO;
use App\DTO\Response\PaginatedResponseDTO;
use App\DTO\Response\PluginResponseDTO;
use App\DTO\Response\ProjectResponseDTO;
use App\DTO\Response\SearchResponseDTO;
use App\DTO\Response\SuccessResponseDTO;
use App\DTO\Response\TemplateResponseDTO;
use App\DTO\Response\UserProfileResponseDTO;
use App\DTO\Response\UserResponseDTO;
use App\Entity\User;
use App\Entity\Design;
use App\Entity\Project;
use App\Entity\Media;
use App\Entity\Template;
use App\Entity\Layer;
use App\Entity\ExportJob;
use App\Entity\Plugin;

/**
 * Factory service for creating response DTOs
 */
class ResponseDTOFactory
{
    /**
     * Create UserResponseDTO from User entity
     */
    public function createUserResponse(User $user, bool $includeExtendedInfo = false): UserResponseDTO
    {
        if ($includeExtendedInfo) {
            return new UserResponseDTO(
                id: (string) $user->getId(),
                email: $user->getEmail(),
                firstName: $user->getFirstName(),
                lastName: $user->getLastName(),
                username: $user->getUsername(),
                roles: $user->getRoles(),
                avatar: $user->getAvatar(),
                plan: $user->getPlan(),
                emailVerified: $user->getEmailVerified(),
                isActive: $user->getIsActive(),
                createdAt: $user->getCreatedAt()->format('c'),
                lastLoginAt: $user->getLastLoginAt()?->format('c'),
                updatedAt: $user->getUpdatedAt()?->format('c'),
                settings: $user->getSettings(),
                stats: [
                    'projectCount' => count($user->getProjects()),
                    'mediaCount' => count($user->getMediaFiles()),
                    'exportJobCount' => count($user->getExportJobs()),
                ]
            );
        }

        return new UserResponseDTO(
            id: (string) $user->getId(),
            email: $user->getEmail(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            username: $user->getUsername(),
            roles: $user->getRoles(),
            avatar: $user->getAvatar(),
            plan: $user->getPlan()
        );
    }

    /**
     * Create authentication response with token
     */
    public function createAuthResponse(string $message, string $token, User $user): AuthResponseDTO
    {
        return new AuthResponseDTO(
            message: $message,
            token: $token,
            user: $this->createUserResponse($user)
        );
    }

    /**
     * Create user profile response
     */
    public function createUserProfileResponse(User $user): UserProfileResponseDTO
    {
        return new UserProfileResponseDTO(
            user: $this->createUserResponse($user, true)
        );
    }

    /**
     * Create success response
     */
    public function createSuccessResponse(string $message): SuccessResponseDTO
    {
        return new SuccessResponseDTO($message);
    }

    /**
     * Create error response
     */
    public function createErrorResponse(
        string $message, 
        array $details = [], 
        ?string $code = null
    ): ErrorResponseDTO {
        return new ErrorResponseDTO(
            message: $message,
            details: $details,
            code: $code
        );
    }

    /**
     * Create design response from Design entity
     */
    public function createDesignResponse(Design $design, string $message = 'Design retrieved successfully'): DesignResponseDTO
    {
        $designData = [
            'id' => $design->getId(),
            'uuid' => $design->getUuid(),
            'title' => $design->getTitle(),
            'data' => $design->getData(),
            'width' => $design->getWidth(),
            'height' => $design->getHeight(),
            'background' => $design->getBackground(),
            'thumbnail' => $design->getThumbnail(),
            'hasAnimation' => $design->getHasAnimation(),
            'fps' => $design->getFps(),
            'duration' => $design->getDuration(),
            'projectId' => $design->getProject()?->getId(),
            'createdAt' => $design->getCreatedAt()->format('c'),
            'updatedAt' => $design->getUpdatedAt()?->format('c')
        ];

        return new DesignResponseDTO(
            success: true,
            message: $message,
            design: $designData
        );
    }

    /**
     * Create project response from Project entity
     */
    public function createProjectResponse(Project $project, string $message = 'Project retrieved successfully'): ProjectResponseDTO
    {
        $projectData = [
            'id' => $project->getId(),
            'uuid' => $project->getUuid(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            'thumbnail' => $project->getThumbnail(),
            'isPublic' => $project->getIsPublic(),
            'userId' => $project->getUser()->getId(),
            'designsCount' => $project->getDesigns()->count(),
            'createdAt' => $project->getCreatedAt()?->format('c'),
            'updatedAt' => $project->getUpdatedAt()?->format('c')
        ];

        return new ProjectResponseDTO(
            success: true,
            message: $message,
            project: $projectData
        );
    }

    /**
     * Create media response from Media entity
     */
    public function createMediaResponse(Media $media, string $message = 'Media retrieved successfully'): MediaResponseDTO
    {
        $mediaData = [
            'id' => $media->getId(),
            'uuid' => $media->getUuid(),
            'name' => $media->getName(),
            'type' => $media->getType(),
            'mimeType' => $media->getMimeType(),
            'size' => $media->getSize(),
            'url' => $media->getUrl(),
            'thumbnailUrl' => $media->getThumbnailUrl(),
            'width' => $media->getWidth(),
            'height' => $media->getHeight(),
            'duration' => $media->getDuration(),
            'source' => $media->getSource(),
            'sourceId' => $media->getSourceId(),
            'metadata' => $media->getMetadata(),
            'tags' => $media->getTags(),
            'attribution' => $media->getAttribution(),
            'license' => $media->getLicense(),
            'isPremium' => $media->isIsPremium(),
            'isActive' => $media->isIsActive(),
            'uploadedBy' => $media->getUser() ? [
                'id' => $media->getUser()->getId(),
                'username' => $media->getUser()->getUsername(),
            ] : null,
            'createdAt' => $media->getCreatedAt()->format('c'),
            'updatedAt' => $media->getUpdatedAt()?->format('c'),
        ];

        return new MediaResponseDTO(
            success: true,
            message: $message,
            media: $mediaData
        );
    }

    /**
     * Create template response from Template entity
     */
    public function createTemplateResponse(Template $template, string $message = ''): TemplateResponseDTO
    {
        $templateData = [
            'id' => $template->getId(),
            'uuid' => $template->getUuid(),
            'name' => $template->getName(),
            'description' => $template->getDescription(),
            'category' => $template->getCategory(),
            'tags' => $template->getTags(),
            'thumbnailUrl' => $template->getThumbnailUrl(),
            'previewUrl' => $template->getPreviewUrl(),
            'width' => $template->getWidth(),
            'height' => $template->getHeight(),
            'canvasSettings' => $template->getCanvasSettings(),
            'layers' => $template->getLayers(),
            'isPremium' => $template->isIsPremium(),
            'isActive' => $template->isIsActive(),
            'rating' => (float) $template->getRating(),
            'ratingCount' => $template->getRatingCount(),
            'usageCount' => $template->getUsageCount(),
            'createdBy' => $template->getCreatedBy() ? [
                'id' => $template->getCreatedBy()->getId(),
                'username' => $template->getCreatedBy()->getUsername(),
            ] : null,
            'createdAt' => $template->getCreatedAt()->format('c'),
            'updatedAt' => $template->getUpdatedAt()?->format('c'),
        ];

        return new TemplateResponseDTO(
            success: true,
            message: $message ?: 'Template retrieved successfully',
            template: $templateData
        );
    }

    public function createTemplateListResponse(array $templates, int $total, int $page, int $limit, string $message = ''): TemplateResponseDTO
    {
        $templatesData = array_map(function (Template $template) {
            return [
                'id' => $template->getId(),
                'uuid' => $template->getUuid(),
                'name' => $template->getName(),
                'description' => $template->getDescription(),
                'category' => $template->getCategory(),
                'tags' => $template->getTags(),
                'thumbnailUrl' => $template->getThumbnailUrl(),
                'previewUrl' => $template->getPreviewUrl(),
                'width' => $template->getWidth(),
                'height' => $template->getHeight(),
                'isPremium' => $template->isIsPremium(),
                'isActive' => $template->isIsActive(),
                'rating' => (float) $template->getRating(),
                'ratingCount' => $template->getRatingCount(),
                'usageCount' => $template->getUsageCount(),
                'createdAt' => $template->getCreatedAt()->format('c'),
                'updatedAt' => $template->getUpdatedAt()?->format('c'),
            ];
        }, $templates);

        return new TemplateResponseDTO(
            success: true,
            message: $message ?: 'Templates retrieved successfully',
            templates: $templatesData,
            total: $total,
            page: $page,
            totalPages: (int) ceil((float) $total / (float) $limit)
        );
    }

    /**
     * Create layer response from Layer entity
     */
    public function createLayerResponse(Layer $layer, string $message = 'Success'): LayerResponseDTO
    {
        return new LayerResponseDTO(
            true,
            $message,
            [
                'id' => $layer->getId(),
                'uuid' => $layer->getUuid(),
                'name' => $layer->getName(),
                'type' => $layer->getType(),
                'properties' => $layer->getProperties(),
                'transform' => $layer->getTransform(),
                'zIndex' => $layer->getZIndex(),
                'visible' => $layer->isVisible(),
                'locked' => $layer->isLocked(),
                'opacity' => $layer->getOpacity(),
                'animations' => $layer->getAnimations(),
                'mask' => $layer->getMask(),
                'design' => $layer->getDesign() ? [
                    'id' => $layer->getDesign()->getId(),
                    'uuid' => $layer->getDesign()->getUuid(),
                    'name' => $layer->getDesign()->getName(),
                ] : null,
                'parent' => $layer->getParent() ? [
                    'id' => $layer->getParent()->getId(),
                    'uuid' => $layer->getParent()->getUuid(),
                    'name' => $layer->getParent()->getName(),
                ] : null,
                'children' => $layer->getChildren()->map(function (Layer $child) {
                    return [
                        'id' => $child->getId(),
                        'uuid' => $child->getUuid(),
                        'name' => $child->getName(),
                        'type' => $child->getType(),
                        'zIndex' => $child->getZIndex(),
                    ];
                })->toArray(),
                'createdAt' => $layer->getCreatedAt()->format('c'),
                'updatedAt' => $layer->getUpdatedAt()?->format('c'),
            ]
        );
    }

    /**
     * Create export job response from ExportJob entity
     */
    public function createExportJobResponse(ExportJob $exportJob, string $message = '', bool $detailed = false): ExportJobResponseDTO
    {
        $data = [
            'id' => $exportJob->getId(),
            'designId' => $exportJob->getDesign()->getId(),
            'format' => $exportJob->getFormat(),
            'status' => $exportJob->getStatus(),
            'progress' => $exportJob->getProgress(),
            'createdAt' => $exportJob->getCreatedAt()->format('c'),
        ];

        if ($detailed) {
            $data['width'] = $exportJob->getWidth();
            $data['height'] = $exportJob->getHeight();
            $data['quality'] = $exportJob->getQuality();
            $data['scale'] = $exportJob->getScale();
            $data['transparent'] = $exportJob->isTransparent();
            $data['backgroundColor'] = $exportJob->getBackgroundColor();
            $data['animationSettings'] = $exportJob->getAnimationSettings();
            $data['fileSize'] = $exportJob->getFileSize();
            $data['fileName'] = $exportJob->getFileName();
            $data['mimeType'] = $exportJob->getMimeType();
            $data['processingTimeMs'] = $exportJob->getProcessingTimeMs();
            $data['metadata'] = $exportJob->getMetadata();
            
            if ($exportJob->getErrorMessage()) {
                $data['errorMessage'] = $exportJob->getErrorMessage();
                $data['errorDetails'] = $exportJob->getErrorDetails();
            }
            
            if ($exportJob->getStartedAt()) {
                $data['startedAt'] = $exportJob->getStartedAt()->format('c');
            }
            
            if ($exportJob->getCompletedAt()) {
                $data['completedAt'] = $exportJob->getCompletedAt()->format('c');
            }
            
            if ($exportJob->getExpiresAt()) {
                $data['expiresAt'] = $exportJob->getExpiresAt()->format('c');
                $data['isExpired'] = $exportJob->isExpired();
            }
        }

        return new ExportJobResponseDTO(
            success: true,
            message: $message ?: 'Export job retrieved successfully',
            job: $data
        );
    }

    public function createExportJobListResponse(array $exportJobs, int $total, int $page, int $limit, string $message = ''): ExportJobResponseDTO
    {
        $jobsData = array_map(function (ExportJob $exportJob) {
            return [
                'id' => $exportJob->getId(),
                'designId' => $exportJob->getDesign()->getId(),
                'format' => $exportJob->getFormat(),
                'status' => $exportJob->getStatus(),
                'progress' => $exportJob->getProgress(),
                'createdAt' => $exportJob->getCreatedAt()->format('c'),
            ];
        }, $exportJobs);

        return new ExportJobResponseDTO(
            success: true,
            message: $message ?: 'Export jobs retrieved successfully',
            jobs: $jobsData,
            total: $total,
            page: $page,
            totalPages: (int) ceil((float) $total / (float) $limit)
        );
    }

    /**
     * Create plugin response from Plugin entity
     */
    public function createPluginResponse(Plugin $plugin, string $message = 'Plugin retrieved successfully'): PluginResponseDTO
    {
        $pluginData = [
            'id' => $plugin->getId(),
            'name' => $plugin->getName(),
            'description' => $plugin->getDescription(),
            'categories' => $plugin->getCategories(),
            'version' => $plugin->getVersion(),
            'status' => $plugin->getStatus(),
            'install_count' => $plugin->getInstallCount(),
            'rating' => $plugin->getRating(),
            'rating_count' => $plugin->getRatingCount(),
            'permissions' => $plugin->getPermissions(),
            'manifest' => $plugin->getManifest(),
            'security_scan' => $plugin->getSecurityScan(),
            'review_notes' => $plugin->getReviewNotes(),
            'created_at' => $plugin->getCreatedAt()?->format('c'),
            'updated_at' => $plugin->getUpdatedAt()?->format('c'),
            'reviewed_at' => $plugin->getReviewedAt()?->format('c'),
            'developer' => [
                'id' => $plugin->getUser()->getId(),
                'username' => $plugin->getUser()->getUsername(),
                'email' => $plugin->getUser()->getEmail(),
            ],
        ];

        if ($plugin->getReviewedBy()) {
            $pluginData['reviewed_by'] = [
                'id' => $plugin->getReviewedBy()->getId(),
                'username' => $plugin->getReviewedBy()->getUsername(),
            ];
        }

        return new PluginResponseDTO(
            success: true,
            message: $message,
            plugin: $pluginData
        );
    }

    /**
     * Create plugin list response from array of Plugin entities
     */
    public function createPluginListResponse(
        array $plugins, 
        int $page, 
        int $limit, 
        int $total, 
        string $message = 'Plugins retrieved successfully'
    ): PluginResponseDTO {
        $pluginsData = array_map(function (Plugin $plugin) {
            return [
                'id' => $plugin->getId(),
                'name' => $plugin->getName(),
                'description' => $plugin->getDescription(),
                'categories' => $plugin->getCategories(),
                'version' => $plugin->getVersion(),
                'status' => $plugin->getStatus(),
                'install_count' => $plugin->getInstallCount(),
                'rating' => $plugin->getRating(),
                'rating_count' => $plugin->getRatingCount(),
                'created_at' => $plugin->getCreatedAt()?->format('c'),
                'updated_at' => $plugin->getUpdatedAt()?->format('c'),
                'developer' => [
                    'id' => $plugin->getUser()->getId(),
                    'username' => $plugin->getUser()->getUsername(),
                    'email' => $plugin->getUser()->getEmail(),
                ],
            ];
        }, $plugins);

        return new PluginResponseDTO(
            success: true,
            message: $message,
            plugins: $pluginsData,
            total: $total,
            page: $page,
            totalPages: (int) ceil($total / $limit)
        );
    }

    /**
     * Create search response with results
     */
    public function createSearchResponse(array $results, int $total, int $page, int $limit, string $query): SearchResponseDTO
    {
        return new SearchResponseDTO(
            success: true,
            message: 'Search completed successfully',
            results: $results,
            query: $query,
            total: $total,
            page: $page,
            totalPages: (int) ceil((float) $total / (float) $limit)
        );
    }

    /**
     * Create paginated response with data array
     * @template T
     * @param T[] $data
     */
    public function createPaginatedResponse(array $data, int $page, int $limit, int $total, string $message = 'Success'): PaginatedResponseDTO
    {
        $totalPages = (int) ceil((float) $total / (float) $limit);
        
        return new PaginatedResponseDTO(
            data: $data,
            page: $page,
            limit: $limit,
            total: $total,
            totalPages: $totalPages,
            message: $message
        );
    }
}
