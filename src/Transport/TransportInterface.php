<?php

declare(strict_types=1);

namespace Traffman\Sdk\Transport;

use Traffman\Sdk\Exception\TransportException;
use Traffman\Sdk\Http\Request;
use Traffman\Sdk\Http\Response;

/**
 * Sends an HTTP request over the network.
 *
 * Implement it to run the SDK on any platform: wrap the platform HTTP API (WordPress `wp_remote_request()`,
 * Drupal/Laravel Guzzle, Symfony HttpClient, ...) or use one of the bundled transports:
 * {@see Psr18Transport} (any PSR-18 client) or {@see CurlTransport} (ext-curl, no dependencies).
 *
 * The transport must not throw on HTTP error statuses (4xx/5xx): it returns them as a {@see Response}.
 * It must not follow redirects.
 */
interface TransportInterface
{
    /**
     * @throws TransportException When the request could not be sent or no response was received.
     */
    public function send(Request $request): Response;
}
