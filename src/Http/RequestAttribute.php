<?php

declare(strict_types=1);

namespace Traffman\Sdk\Http;

/**
 * Names of the request attributes set by the SDK (see {@see Request::getAttribute()}).
 */
final class RequestAttribute
{
    /**
     * `string` – name of the API operation, e.g. `client.iam` (see {@see Operation}).
     */
    public const OPERATION = 'traffman.operation';

    /**
     * `bool` – the request is signed with the client API credentials.
     */
    public const AUTHENTICATED = 'traffman.authenticated';

    /**
     * `string|null` – API key of the credentials the request is signed with (set for the signed requests only).
     */
    public const API_KEY = 'traffman.api_key';

    /**
     * `string|null` – IP address of the site visitor sent in the `X-User-Client-IP` header.
     * Overrides the configured user IP resolver for one request.
     */
    public const USER_IP = 'traffman.user_ip';

    private function __construct()
    {
    }
}
