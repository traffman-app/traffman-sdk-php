<?php

declare(strict_types=1);

namespace Traffman\Sdk\Tests\Support;

use Traffman\Sdk\Http\Request;

/**
 * Reproduces the signature checks of the Traffman API (backend `RequestSignatureValidator`,
 * `QuerySignatureValidator`, `ClientRequestIpRetriever`, `ClientConfirmer`) independently of the SDK code.
 */
final class ServerSignature
{
    /**
     * @return array{key: string, ts: int, sig: string}|null
     */
    public static function extractAuthorization(Request $request): ?array
    {
        preg_match(
            '/^Signature key=([a-zA-Z0-9]+),ts=(\d{10}),sig=([a-zA-Z0-9-_]+)$/',
            $request->getHeader('Authorization') ?? '',
            $matches,
        );

        if (!$matches) {
            return null;
        }

        return ['key' => $matches[1], 'ts' => (int) $matches[2], 'sig' => $matches[3]];
    }

    public static function isRequestValid(Request $request, string $secret): bool
    {
        $parts = self::extractAuthorization($request);

        if ($parts === null) {
            return false;
        }

        $url = parse_url($request->getUrl());
        parse_str($url['query'] ?? '', $query);
        ksort($query);

        $data = implode("\n", [
            $request->getMethod(),
            $url['path'] ?? '',
            http_build_query($query, '', '&', PHP_QUERY_RFC3986),
            hash('sha256', $request->getBody()),
            $parts['ts'],
        ]);

        return self::sign($data, $secret) === $parts['sig'];
    }

    public static function isQueryUrlValid(string $url, string $secret): bool
    {
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);

        $signature = $query['sig'] ?? null;
        $timestamp = $query['ts'] ?? null;
        unset($query['sig']);
        ksort($query);

        if (!is_string($signature) || !is_string($timestamp)) {
            return false;
        }

        $data = implode("\n", [
            'GET',
            $parts['path'] ?? '',
            http_build_query($query, '', '&', PHP_QUERY_RFC3986),
            (int) $timestamp,
        ]);

        return self::sign($data, $secret) === $signature;
    }

    public static function confirmationSignature(string $apiKey, string $callbackUrl, string $secret): string
    {
        return self::sign((string) json_encode(['key' => $apiKey, 'callbackUrl' => $callbackUrl]), $secret);
    }

    public static function sign(string $data, string $secret): string
    {
        return rtrim(strtr(base64_encode(hash_hmac('sha256', $data, $secret, true)), '+/', '-_'), '=');
    }
}
