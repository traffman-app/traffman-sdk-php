<?php

declare(strict_types=1);

namespace Traffman\Sdk\Http;

use Traffman\Sdk\Exception\TransportException;

/**
 * Handles a request and returns its response (the next link of the middleware chain).
 */
interface RequestHandlerInterface
{
    /**
     * @throws TransportException When the request could not be sent or no response was received.
     */
    public function handle(Request $request): Response;
}
