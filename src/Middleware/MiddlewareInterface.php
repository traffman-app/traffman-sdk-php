<?php

declare(strict_types=1);

namespace Traffman\Sdk\Middleware;

use Traffman\Sdk\Exception\TransportException;
use Traffman\Sdk\Http\Request;
use Traffman\Sdk\Http\RequestHandlerInterface;
use Traffman\Sdk\Http\Response;

/**
 * Wraps the sending of a request: logging, caching, retries, extra headers, metrics, ...
 *
 * A middleware either returns a response itself or delegates to `$next`. The user middleware runs before
 * the request is signed, so it may change the request freely (the signature always covers the final request).
 */
interface MiddlewareInterface
{
    /**
     * @throws TransportException
     */
    public function process(Request $request, RequestHandlerInterface $next): Response;
}
