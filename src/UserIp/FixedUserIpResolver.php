<?php

declare(strict_types=1);

namespace Traffman\Sdk\UserIp;

/**
 * Always returns the given IP (or none), e.g. when the IP is taken from the framework request object.
 */
final class FixedUserIpResolver implements UserIpResolverInterface
{
    public function __construct(
        private readonly ?string $ip = null,
    ) {
    }

    public function resolve(): ?string
    {
        return $this->ip;
    }
}
