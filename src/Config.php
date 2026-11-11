<?php

declare(strict_types=1);

namespace Traffman\Sdk;

use Traffman\Sdk\Exception\InvalidArgumentException;

/**
 * Connection settings of the SDK client.
 */
final class Config
{
    public const DEFAULT_BASE_URL = 'https://api.traffman.com';
    public const DEFAULT_API_VERSION = 'v1';

    public readonly string $baseUrl;

    /**
     * @param string $baseUrl API origin, e.g. a staging or a local server.
     * @param string $apiVersion API version prefix of the paths.
     * @param string|null $userAgent `User-Agent` header (the SDK name and version when null).
     * @param array<string, string> $headers Headers sent with every request
     *     (e.g. `X-Timezone` for the statistics, or `Host` for a forwarded API host).
     */
    public function __construct(
        string $baseUrl = self::DEFAULT_BASE_URL,
        public readonly string $apiVersion = self::DEFAULT_API_VERSION,
        public readonly ?string $userAgent = null,
        public readonly array $headers = [],
    ) {
        $scheme = parse_url($baseUrl, PHP_URL_SCHEME);

        if (!in_array($scheme, ['http', 'https'], true) || parse_url($baseUrl, PHP_URL_HOST) === null) {
            throw new InvalidArgumentException(sprintf('The base URL "%s" is not a valid HTTP(S) URL.', $baseUrl));
        }

        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Absolute URL of an API path, e.g. `clients/iam` → `https://api.traffman.com/v1/clients/iam`.
     */
    public function buildUrl(string $path): string
    {
        return $this->baseUrl . '/' . trim($this->apiVersion, '/') . '/' . ltrim($path, '/');
    }

    public function getUserAgent(): string
    {
        return $this->userAgent ?? sprintf('%s/%s PHP/%s', Version::NAME, Version::VERSION, PHP_VERSION);
    }
}
