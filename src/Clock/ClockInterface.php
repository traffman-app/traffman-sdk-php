<?php

declare(strict_types=1);

namespace Traffman\Sdk\Clock;

use DateTimeImmutable;

/**
 * Source of the current time (the timestamp of the request signatures).
 *
 * The signature is accepted by the API within a few minutes of the server time, so a host with a skewed clock
 * may pass an implementation with an offset. Compatible with PSR-20 `ClockInterface`.
 */
interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
