<?php

declare(strict_types=1);

namespace Traffman\Sdk\Signature;

use SensitiveParameter;

/**
 * HMAC-SHA256 signatures of the Traffman API (base64url without padding).
 *
 * All the signatures exchanged with the API are built here, so they can be reused by a platform integration
 * (e.g. to sign the content redirects or to verify the webhooks without the full SDK client).
 */
final class Signer
{
    private const ALGORITHM = 'sha256';

    public function __construct(
        #[SensitiveParameter]
        private readonly string $secret,
    ) {
    }

    /**
     * Signs arbitrary data.
     */
    public function sign(string $data): string
    {
        return rtrim(strtr(base64_encode(hash_hmac(self::ALGORITHM, $data, $this->secret, true)), '+/', '-_'), '=');
    }

    /**
     * Compares the signature of the data with the given one in constant time.
     */
    public function verify(string $data, string $signature): bool
    {
        return hash_equals($this->sign($data), $signature);
    }

    /**
     * Signature of an API request (the `sig` part of the `Authorization: Signature ...` header).
     */
    public function signRequest(string $method, string $url, string $body, int $timestamp): string
    {
        $url = UrlCanonicalizer::parse($url);

        return $this->sign(implode("\n", [
            strtoupper($method),
            $url['path'],
            $url['query'],
            hash(self::ALGORITHM, $body),
            (string) $timestamp,
        ]));
    }

    /**
     * Signature of a URL authorized by its query (`key`, `ts` and `sig` parameters); the `sig` is ignored.
     */
    public function signQuery(string $url, int $timestamp): string
    {
        $url = UrlCanonicalizer::parse($url, ['sig']);

        return $this->sign(implode("\n", [
            'GET',
            $url['path'],
            $url['query'],
            (string) $timestamp,
        ]));
    }

    /**
     * Signature of the client confirmation request, also sent back by the API in the confirmation webhook.
     */
    public function signConfirmation(string $apiKey, string $callbackUrl): string
    {
        // The API signs the JSON with the default flags (escaped slashes and unicode), so it is reproduced as is.
        return $this->sign((string) json_encode(['key' => $apiKey, 'callbackUrl' => $callbackUrl]));
    }

    /**
     * Signature of the site visitor IP (the `X-User-Client-IP-Signature` header).
     */
    public function signUserIp(string $ip): string
    {
        return $this->sign($ip);
    }

    /**
     * Signature of a content redirect shown in a content block.
     *
     * @param string $blockId ID of the block on the page (10 characters `[a-z0-9]`).
     * @param string $contentId ID of the targeted content item (the content redirect ID).
     */
    public function signContentRedirect(string $blockId, string $contentId): string
    {
        return $this->sign($blockId . "\n" . $contentId);
    }
}
