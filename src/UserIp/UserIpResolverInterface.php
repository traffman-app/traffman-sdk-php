<?php

declare(strict_types=1);

namespace Traffman\Sdk\UserIp;

/**
 * Resolves the IP address of the current site visitor.
 *
 * The IP is sent (signed) in the `X-User-Client-IP` header: the API needs it to pick the targeted content
 * and to limit the requests per visitor rather than per site.
 */
interface UserIpResolverInterface
{
    /**
     * @return string|null Null outside a visitor request (CLI, cron, queue workers).
     */
    public function resolve(): ?string;
}
