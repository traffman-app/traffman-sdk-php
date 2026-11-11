<?php

declare(strict_types=1);

namespace Traffman\Sdk\Signature;

/**
 * Canonical form of a URL for the signatures, the same as the API builds from the incoming request:
 * the path as is and the query parameters sorted by name and encoded by RFC 3986.
 */
final class UrlCanonicalizer
{
    /**
     * @param string[] $excludeParameters Query parameters left out (e.g. the signature itself).
     *
     * @return array{path: string, query: string}
     */
    public static function parse(string $url, array $excludeParameters = []): array
    {
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        return [
            'path' => is_string($path) ? $path : '',
            'query' => self::canonicalizeQuery(is_string($query) ? $query : '', $excludeParameters),
        ];
    }

    /**
     * @param string[] $excludeParameters
     */
    public static function canonicalizeQuery(string $query, array $excludeParameters = []): string
    {
        parse_str($query, $parameters);

        foreach ($excludeParameters as $name) {
            unset($parameters[$name]);
        }

        ksort($parameters);

        return http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Builds a query string the same way (null values are left out).
     *
     * @param array<array-key, mixed> $parameters
     */
    public static function buildQuery(array $parameters): string
    {
        return http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }

    private function __construct()
    {
    }
}
