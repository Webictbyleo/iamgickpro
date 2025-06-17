<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\TypedResponseTrait;
use App\Service\ResponseDTOFactory;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Public Media Controller
 * 
 * Handles public media operations that don't require authentication.
 * This includes media proxy functionality for external stock media URLs.
 */
#[Route('/api/media', name: 'api_public_media_')]
class PublicMediaController extends AbstractController
{
    use TypedResponseTrait;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ResponseDTOFactory $responseDTOFactory,
        private readonly LoggerInterface $logger,
        private readonly CacheItemPoolInterface $cache,
    ) {}

    /**
     * Proxy external media URLs (for stock media with authentication requirements)
     * 
     * This endpoint acts as a proxy for external media URLs that require authentication
     * or have CORS restrictions. It handles the authentication on the server side
     * and streams the content to the client. This endpoint is publicly accessible
     * since it serves stock media content.
     * 
     * Caching Strategy:
     * - Unsplash regular/large images (w > 400 or original): 30 days
     * - Unsplash small/thumbnails (w <= 400): 7 days
     * - Pexels large images (used in designs): 30 days
     * - Pexels thumbnails: 7 days
     * - Iconfinder download URLs: 90 days (vector assets)
     * - Iconfinder previews: 30 days
     * - Other media: 6 hours
     * 
     * @param string $encodedUrl Base64 encoded URL to proxy
     * @return Response Streamed media content with proper headers
     */
    #[Route('/proxy/{encodedUrl}', name: 'proxy_media', methods: ['GET'])]
    public function proxyMedia(string $encodedUrl, Request $request): Response
    {
        try {
            // Decode the URL
            $url = base64_decode($encodedUrl);
            if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
                return new Response('Invalid URL provided', Response::HTTP_BAD_REQUEST, [
                    'Content-Type' => 'text/plain'
                ]);
            }

            // Create cache key based on URL
            $cacheKey = 'media_proxy_' . md5($url);
            $cacheItem = $this->cache->getItem($cacheKey);

            // Determine cache duration based on URL type and usage
            $cacheDuration = $this->determineCacheDuration($url);

            // Return cached response if available and valid
            if ($cacheItem->isHit()) {
                $cachedData = $cacheItem->get();
                $this->logger->info('Serving cached proxied media', ['url' => $url, 'cache_duration' => $cacheDuration]);
                
                $response = new Response($cachedData['content']);
                $response->headers->set('Content-Type', $cachedData['contentType']);
                $response->headers->set('Cache-Control', 'public, max-age=' . $cacheDuration);
                $response->headers->set('X-Content-Type-Options', 'nosniff');
                $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
                $response->headers->set('X-Cache', 'HIT');
                
                // Handle conditional requests for caching
                $response->isNotModified($request);
                return $response;
            }

            // Only allow whitelisted domains for security
            $allowedDomains = [
                'iconfinder.com',
                'api.iconfinder.com',
                'cdn.iconfinder.com',
                'cdn1.iconfinder.com',
                'cdn2.iconfinder.com',
                'cdn3.iconfinder.com',
                'cdn4.iconfinder.com',
                'images.unsplash.com',
                'api.unsplash.com',
                'images.pexels.com',
                'api.pexels.com'
            ];

            $urlParts = parse_url($url);
            $domain = $urlParts['host'] ?? '';
            
            $isAllowed = false;
            foreach ($allowedDomains as $allowedDomain) {
                if ($domain === $allowedDomain || str_ends_with($domain, '.' . $allowedDomain)) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                $this->logger->warning('Attempt to proxy unauthorized domain', [
                    'url' => $url,
                    'domain' => $domain
                ]);
                return new Response('Domain not allowed', Response::HTTP_FORBIDDEN, [
                    'Content-Type' => 'text/plain'
                ]);
            }

            $this->logger->info('Fetching and caching external media URL', [
                'url' => $url,
                'domain' => $domain,
                'cache_duration_seconds' => $cacheDuration,
                'cache_duration_days' => round($cacheDuration / 86400, 2)
            ]);

            // Fetch the external content with appropriate headers
            $headers = ['Accept' => 'image/*,*/*'];
            
            // Add authentication for specific services
            if (str_contains($domain, 'iconfinder.com')) {
                // For Iconfinder, we might need to add API key for certain URLs
                // This would use the same API key as the IconfinderService
                $iconfinderApiKey = $_ENV['ICONFINDER_API_KEY'] ?? null;
                if ($iconfinderApiKey) {
                    $headers['Authorization'] = 'Bearer ' . $iconfinderApiKey;
                }
            }

            $response = $this->httpClient->request('GET', $url, [
                'headers' => $headers,
                'timeout' => 30,
                'max_redirects' => 3
            ]);

            $contentType = $response->getHeaders()['content-type'][0] ?? 'application/octet-stream';
            $content = $response->getContent();

            // Cache the response for future requests with appropriate duration
            $cacheData = [
                'content' => $content,
                'contentType' => $contentType
            ];
            $cacheItem->set($cacheData);
            $cacheItem->expiresAfter($cacheDuration);
            $this->cache->save($cacheItem);

            // Create streaming response
            $streamResponse = new Response($content);
            $streamResponse->headers->set('Content-Type', $contentType);
            $streamResponse->headers->set('Cache-Control', 'public, max-age=' . $cacheDuration);
            $streamResponse->headers->set('X-Content-Type-Options', 'nosniff');
            $streamResponse->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $streamResponse->headers->set('X-Cache', 'MISS');

            // Handle conditional requests for caching
            $streamResponse->isNotModified($request);

            return $streamResponse;

        } catch (ClientExceptionInterface $e) {
            $this->logger->error('HTTP client error while proxying media', [
                'url' => $url ?? 'unknown',
                'error' => $e->getMessage(),
                'status_code' => $e->getResponse()?->getStatusCode()
            ]);

            $statusCode = $e->getResponse()?->getStatusCode() ?? Response::HTTP_BAD_GATEWAY;
            return new Response('Failed to fetch external media: ' . $e->getMessage(), $statusCode, [
                'Content-Type' => 'text/plain'
            ]);

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Transport error while proxying media', [
                'url' => $url ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return new Response('Failed to connect to external media source', Response::HTTP_BAD_GATEWAY, [
                'Content-Type' => 'text/plain'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Unexpected error while proxying media', [
                'url' => $url ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return new Response('Internal server error while fetching media', Response::HTTP_INTERNAL_SERVER_ERROR, [
                'Content-Type' => 'text/plain'
            ]);
        }
    }

    /**
     * Determine appropriate cache duration based on URL type and usage pattern
     * 
     * For Unsplash: Uses width (w) query parameter to distinguish between thumbnail/small (≤400px) 
     * and regular/large images (>400px or original), since both have "photo-" in the path.
     * 
     * @param string $url The media URL to analyze
     * @return int Cache duration in seconds
     */
    private function determineCacheDuration(string $url): int
    {
        // Parse URL to determine domain and path characteristics
        $urlParts = parse_url($url);
        $domain = $urlParts['host'] ?? '';
        $path = $urlParts['path'] ?? '';
        $query = $urlParts['query'] ?? '';

        // Unsplash images - cache for longer periods since they're used in designs
        if (str_contains($domain, 'unsplash.com')) {
            // Parse query parameters to get width
            parse_str($query, $queryParams);
            $width = isset($queryParams['w']) ? (int)$queryParams['w'] : 0;
            
            // Small/thumbnail images (width <= 400) - cache for 7 days
            if ($width > 0 && $width <= 400) {
                $this->logger->info('Setting medium cache for Unsplash thumbnail/small image', [
                    'url' => $url, 
                    'width' => $width
                ]);
                return 7 * 24 * 3600; // 7 days
            }
            
            // Regular/large size images (width > 400 or no width specified) - cache for 30 days
            // This includes cases where width is not specified (original size) or width > 400
            if ($width > 400 || $width === 0) {
                $this->logger->info('Setting long cache for Unsplash regular/large image', [
                    'url' => $url, 
                    'width' => $width ?: 'original'
                ]);
                return 30 * 24 * 3600; // 30 days
            }
            
            // Default for other Unsplash images - cache for 3 days
            return 3 * 24 * 3600; // 3 days
        }

        // Pexels images - similar strategy as Unsplash
        if (str_contains($domain, 'pexels.com')) {
            // Large images used in designs - cache for 30 days
            if (str_contains($path, '/photos/') && !str_contains($path, 'tiny')) {
                $this->logger->info('Setting long cache for Pexels large image', ['url' => $url]);
                return 30 * 24 * 3600; // 30 days
            }
            
            // Thumbnails and small images - cache for 7 days
            return 7 * 24 * 3600; // 7 days
        }

        // Iconfinder icons - cache for very long periods since they're vector assets
        if (str_contains($domain, 'iconfinder.com')) {
            // Download URLs for icons (used in designs) - cache for 90 days
            if (str_contains($path, '/download') || str_contains($query, 'download')) {
                $this->logger->info('Setting very long cache for Iconfinder download', ['url' => $url]);
                return 90 * 24 * 3600; // 90 days
            }
            
            // Preview images - cache for 30 days
            return 30 * 24 * 3600; // 30 days
        }

        // Default cache duration for other media - 6 hours
        return 6 * 3600; // 6 hours
    }
}
