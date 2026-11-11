<?php

declare(strict_types=1);

namespace Traffman\Sdk\Cache;

/**
 * Minimal key-value storage for {@see \Traffman\Sdk\Middleware\CacheMiddleware}.
 *
 * Implement it over the platform cache (WordPress transients, Drupal cache bins, Laravel/Symfony cache)
 * or use {@see Psr16Cache} with any PSR-16 cache.
 */
interface CacheInterface
{
    public function get(string $key): ?string;

    /**
     * @param int $ttl Lifetime in seconds.
     */
    public function set(string $key, string $value, int $ttl): void;

    public function delete(string $key): void;
}
