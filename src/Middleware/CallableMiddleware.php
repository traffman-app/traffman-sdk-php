<?php

declare(strict_types=1);

namespace Traffman\Sdk\Middleware;

use Closure;
use Traffman\Sdk\Http\Request;
use Traffman\Sdk\Http\RequestHandlerInterface;
use Traffman\Sdk\Http\Response;

/**
 * Middleware from a closure: `fn (Request $request, RequestHandlerInterface $next): Response`.
 */
final class CallableMiddleware implements MiddlewareInterface
{
    /**
     * @param Closure(Request, RequestHandlerInterface): Response $callback
     */
    public function __construct(
        private readonly Closure $callback,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        return ($this->callback)($request, $next);
    }
}
