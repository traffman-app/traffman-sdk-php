<?php

declare(strict_types=1);

namespace Traffman\Sdk\Middleware;

use Traffman\Sdk\Http\Request;
use Traffman\Sdk\Http\RequestHandlerInterface;
use Traffman\Sdk\Http\Response;

/**
 * Adds headers to every request, unless the request already has them.
 *
 * E.g. `['X-Timezone' => 'Europe/Kyiv']` or `['Host' => 'api.local']` for a forwarded API host.
 */
final class HeadersMiddleware implements MiddlewareInterface
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly array $headers,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        foreach ($this->headers as $name => $value) {
            if (!$request->hasHeader($name)) {
                $request = $request->withHeader($name, $value);
            }
        }

        return $next->handle($request);
    }
}
