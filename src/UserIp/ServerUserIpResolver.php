<?php

declare(strict_types=1);

namespace Traffman\Sdk\UserIp;

/**
 * Takes the visitor IP from the server parameters (`$_SERVER` by default).
 *
 * By default only `REMOTE_ADDR` is trusted. When the site is behind a reverse proxy or a CDN, enable the
 * proxy headers explicitly ({@see self::withProxyHeaders()}): they are set by the client and can be forged
 * when the request does not pass through the proxy.
 */
final class ServerUserIpResolver implements UserIpResolverInterface
{
    /**
     * Headers set by the common proxies and CDNs, in the order of preference.
     */
    public const PROXY_HEADERS = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
    ];

    /**
     * @param string[] $parameters Server parameters checked in order; the first valid IP wins.
     * @param array<string, mixed>|null $server Server parameters (`$_SERVER` when null).
     */
    public function __construct(
        private readonly array $parameters = ['REMOTE_ADDR'],
        private readonly ?array $server = null,
    ) {
    }

    /**
     * @param array<string, mixed>|null $server
     */
    public static function withProxyHeaders(?array $server = null): self
    {
        return new self([...self::PROXY_HEADERS, 'REMOTE_ADDR'], $server);
    }

    public function resolve(): ?string
    {
        $server = $this->server ?? $_SERVER;

        foreach ($this->parameters as $name) {
            $value = $server[$name] ?? null;

            if (!is_string($value) || $value === '') {
                continue;
            }

            // e.g. `X-Forwarded-For: client, proxy1, proxy2`
            $ip = trim(explode(',', $value)[0]);

            if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                return $ip;
            }
        }

        return null;
    }
}
