<?php

declare(strict_types=1);

namespace Traffman\Sdk\Api;

use Traffman\Sdk\Config;
use Traffman\Sdk\Credentials\Credentials;
use Traffman\Sdk\Exception\ApiException;
use Traffman\Sdk\Exception\MissingCredentialsException;
use Traffman\Sdk\Exception\TransportException;
use Traffman\Sdk\Http\Operation;
use Traffman\Sdk\Http\Response;

/**
 * Low-level access to the API: builds, signs and sends a request and turns an error response into an exception.
 *
 * Use it for the endpoints not covered by the resources yet.
 */
interface ApiClientInterface
{
    /**
     * @param string $path Path relative to the API version, e.g. `clients/iam`.
     * @param array<string, mixed> $query Query parameters (null values are left out).
     * @param array<string, mixed>|null $json Body sent as JSON (none when null).
     * @param bool $authenticated Sign the request with the API credentials.
     * @param string $operation Operation name for the middleware (see {@see Operation}).
     * @param array<string, string> $headers Extra headers.
     * @param array<string, mixed> $attributes Extra request attributes
     *     (see {@see \Traffman\Sdk\Http\RequestAttribute}).
     *
     * @return Response A successful (2xx) response.
     *
     * @throws ApiException For an error status (4xx/5xx).
     * @throws TransportException When no response was received.
     * @throws MissingCredentialsException When an authenticated request is sent without credentials.
     */
    public function request(
        string $method,
        string $path,
        array $query = [],
        ?array $json = null,
        bool $authenticated = true,
        string $operation = Operation::CUSTOM,
        array $headers = [],
        array $attributes = [],
    ): Response;

    /**
     * Current credentials (null when the site is not connected).
     */
    public function getCredentials(): ?Credentials;

    /**
     * @throws MissingCredentialsException
     */
    public function requireCredentials(): Credentials;

    public function getConfig(): Config;
}
