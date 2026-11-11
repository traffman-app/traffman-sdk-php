<?php

declare(strict_types=1);

namespace Traffman\Sdk\Middleware;

use Closure;
use JsonException;
use Traffman\Sdk\Cache\CacheInterface;
use Traffman\Sdk\Http\Method;
use Traffman\Sdk\Http\Operation;
use Traffman\Sdk\Http\Request;
use Traffman\Sdk\Http\RequestAttribute;
use Traffman\Sdk\Http\RequestHandlerInterface;
use Traffman\Sdk\Http\Response;

/**
 * Caches successful responses of GET requests.
 *
 * By default it caches the read-only operations (client info, dashboard, statistics) and never the
 * targeted content, which is personal for each site visitor and counted by the API on every request.
 */
final class CacheMiddleware implements MiddlewareInterface
{
    public const DEFAULT_OPERATIONS = [
        Operation::CLIENT_IAM,
        Operation::CLIENT_DASHBOARD,
        Operation::CONTENT_STATISTICS,
    ];

    /**
     * @var Closure(Request): ?int
     */
    private readonly Closure $ttlResolver;

    /**
     * @param CacheInterface $cache Storage of the responses.
     * @param int $ttl Lifetime of a cached response, in seconds.
     * @param string $namespace Prefix of the cache keys. Set it per API key when several clients share a storage.
     * @param (Closure(Request): ?int)|null $ttlResolver Returns the TTL of a request, or null to skip the cache.
     *     It replaces the default policy (`$ttl` for the {@see self::DEFAULT_OPERATIONS}).
     */
    public function __construct(
        private readonly CacheInterface $cache,
        int $ttl = 300,
        private readonly string $namespace = 'traffman',
        ?Closure $ttlResolver = null,
    ) {
        $this->ttlResolver = $ttlResolver ?? static fn(Request $request): ?int => in_array(
            $request->getAttribute(RequestAttribute::OPERATION),
            self::DEFAULT_OPERATIONS,
            true,
        ) ? $ttl : null;
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $ttl = $request->getMethod() === Method::GET ? ($this->ttlResolver)($request) : null;

        if ($ttl === null || $ttl <= 0) {
            return $next->handle($request);
        }

        $key = $this->createKey($request);
        $cached = $this->restore($this->cache->get($key));

        if ($cached !== null) {
            return $cached;
        }

        $response = $next->handle($request);

        if ($response->isSuccessful()) {
            $this->cache->set($key, $this->store($response), $ttl);
        }

        return $response;
    }

    /**
     * Key of a request: the API key, method, URL and headers.
     * The request is signed later, so the `Authorization` header (with its timestamp) is never a part of it.
     */
    public function createKey(Request $request): string
    {
        $headers = array_change_key_case($request->getHeaders());
        ksort($headers);

        $apiKey = $request->getAttribute(RequestAttribute::API_KEY);

        return $this->namespace . '.' . hash(
            'sha256',
            implode("\n", [
                is_string($apiKey) ? $apiKey : '',
                $request->getMethod(),
                $request->getUrl(),
                serialize($headers),
            ]),
        );
    }

    private function store(Response $response): string
    {
        return (string) json_encode([
            'status' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body' => $response->getBody(),
        ], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function restore(?string $value): ?Response
    {
        if ($value === null) {
            return null;
        }

        try {
            $data = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (
            !is_array($data)
            || !is_int($data['status'] ?? null)
            || !is_array($data['headers'] ?? null)
            || !is_string($data['body'] ?? null)
        ) {
            return null;
        }

        /** @var array<string, string> $headers */
        $headers = array_filter($data['headers'], 'is_string');

        return new Response($data['status'], $headers, $data['body']);
    }
}
