<?php

declare(strict_types=1);

namespace Traffman\Sdk\Clock;

use DateTimeImmutable;

/**
 * Clock which always returns the same time (for tests).
 */
final class FrozenClock implements ClockInterface
{
    public function __construct(
        private DateTimeImmutable $now,
    ) {
    }

    public static function fromTimestamp(int $timestamp): self
    {
        return new self((new DateTimeImmutable())->setTimestamp($timestamp));
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function set(DateTimeImmutable $now): void
    {
        $this->now = $now;
    }
}
