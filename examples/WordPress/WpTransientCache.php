<?php

declare(strict_types=1);

namespace Example\WordPress;

use Traffman\Sdk\Cache\CacheInterface;

/**
 * Storage of CacheMiddleware over the WordPress transients (no disk writes).
 */
final class WpTransientCache implements CacheInterface
{
    public function get(string $key): ?string
    {
        $value = get_transient($this->name($key));

        return is_string($value) ? $value : null;
    }

    public function set(string $key, string $value, int $ttl): void
    {
        set_transient($this->name($key), $value, $ttl);
    }

    public function delete(string $key): void
    {
        delete_transient($this->name($key));
    }

    private function name(string $key): string
    {
        // the transient names are limited to 172 characters
        return 'traffman_' . md5($key);
    }
}
