<?php

declare(strict_types=1);

namespace Traffman\Sdk\Cache;

use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Adapter of any PSR-16 cache (requires `psr/simple-cache`).
 *
 * The SDK keys contain dots only besides letters and digits, so they are valid PSR-16 keys.
 */
final class Psr16Cache implements CacheInterface
{
    public function __construct(
        private readonly SimpleCacheInterface $cache,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function get(string $key): ?string
    {
        $value = $this->cache->get($key);

        return is_string($value) ? $value : null;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function set(string $key, string $value, int $ttl): void
    {
        $this->cache->set($key, $value, $ttl);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function delete(string $key): void
    {
        $this->cache->delete($key);
    }
}
