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
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Invalid URL provided');
                return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
            }

            // Create cache key based on URL
            $cacheKey = 'media_proxy_' . md5($url);
            $cacheItem = $this->cache->getItem($cacheKey);

            // Return cached response if available and valid
            if ($cacheItem->isHit()) {
                $cachedData = $cacheItem->get();
                $this->logger->info('Serving cached proxied media', ['url' => $url]);
                
                $response = new Response($cachedData['content']);
                $response->headers->set('Content-Type', $cachedData['contentType']);
                $response->headers->set('Cache-Control', 'public, max-age=3600');
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
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Domain not allowed');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $this->logger->info('Fetching and caching external media URL', [
                'url' => $url,
                'domain' => $domain
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

            // Cache the response for future requests
            $cacheData = [
                'content' => $content,
                'contentType' => $contentType
            ];
            $cacheItem->set($cacheData);
            $cacheItem->expiresAfter(3600); // Cache for 1 hour
            $this->cache->save($cacheItem);

            // Create streaming response
            $streamResponse = new Response($content);
            $streamResponse->headers->set('Content-Type', $contentType);
            $streamResponse->headers->set('Cache-Control', 'public, max-age=3600'); // Cache for 1 hour
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
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to fetch external media: ' . $e->getMessage()
            );
            return $this->errorResponse($errorResponse, $statusCode);

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Transport error while proxying media', [
                'url' => $url ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to connect to external media source'
            );
            return $this->errorResponse($errorResponse, Response::HTTP_BAD_GATEWAY);

        } catch (\Exception $e) {
            $this->logger->error('Unexpected error while proxying media', [
                'url' => $url ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Internal server error while fetching media'
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
