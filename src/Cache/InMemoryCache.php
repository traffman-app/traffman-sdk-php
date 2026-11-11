<?php

declare(strict_types=1);

namespace Traffman\Sdk\Cache;

use Traffman\Sdk\Clock\ClockInterface;
use Traffman\Sdk\Clock\SystemClock;

/**
 * Cache within the current process (e.g. for a long-running worker or tests).
 */
final class InMemoryCache implements CacheInterface
{
    /**
     * @var array<string, array{value: string, expiresAt: int}>
     */
    private array $items = [];

    private readonly ClockInterface $clock;

    public function __construct(?ClockInterface $clock = null)
    {
        $this->clock = $clock ?? new SystemClock();
    }

    public function get(string $key): ?string
    {
        $item = $this->items[$key] ?? null;

        if ($item === null) {
            return null;
        }

        if ($item['expiresAt'] <= $this->clock->now()->getTimestamp()) {
            unset($this->items[$key]);

            return null;
        }

        return $item['value'];
    }

    public function set(string $key, string $value, int $ttl): void
    {
        $this->items[$key] = [
            'value' => $value,
            'expiresAt' => $this->clock->now()->getTimestamp() + $ttl,
        ];
    }

    public function delete(string $key): void
    {
        unset($this->items[$key]);
    }

    public function clear(): void
    {
        $this->items = [];
    }
}
