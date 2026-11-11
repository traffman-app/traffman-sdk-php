<?php

declare(strict_types=1);

namespace Traffman\Sdk\Middleware;

use Traffman\Sdk\Http\Request;
use Traffman\Sdk\Http\RequestHandlerInterface;
use Traffman\Sdk\Http\Response;

/**
 * Runs the middleware in the given order (the first one is the outermost) and then the final handler.
 */
final class MiddlewareChain implements RequestHandlerInterface
{
    /**
     * @param MiddlewareInterface[] $middleware
     */
    public function __construct(
        private readonly array $middleware,
        private readonly RequestHandlerInterface $handler,
    ) {
    }

    public function handle(Request $request): Response
    {
        return $this->createHandler(0)->handle($request);
    }

    private function createHandler(int $index): RequestHandlerInterface
    {
        $middleware = array_values($this->middleware)[$index] ?? null;

        if ($middleware === null) {
            return $this->handler;
        }

        $next = $this->createHandler($index + 1);

        return new class ($middleware, $next) implements RequestHandlerInterface {
            public function __construct(
                private readonly MiddlewareInterface $middleware,
                private readonly RequestHandlerInterface $next,
            ) {
            }

            public function handle(Request $request): Response
            {
                return $this->middleware->process($request, $this->next);
            }
        };
    }
}
