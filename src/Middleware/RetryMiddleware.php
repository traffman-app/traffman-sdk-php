<?php

declare(strict_types=1);

namespace Traffman\Sdk\Middleware;

use Closure;
use Traffman\Sdk\Exception\TransportException;
use Traffman\Sdk\Http\Method;
use Traffman\Sdk\Http\Request;
use Traffman\Sdk\Http\RequestHandlerInterface;
use Traffman\Sdk\Http\Response;

/**
 * Retries a request after a network error, HTTP 429 or a temporary server error (502, 503, 504).
 *
 * The delay grows exponentially; for HTTP 429 the `Retry-After` header is respected, but a request is not retried
 * when the server asks to wait longer than `$maxDelay`. Only idempotent methods are retried by default,
 * so the client confirmation (POST) is never sent twice.
 */
final class RetryMiddleware implements MiddlewareInterface
{
    private const RETRYABLE_STATUS_CODES = [429, 502, 503, 504];

    /**
     * @var Closure(float): void
     */
    private readonly Closure $sleeper;

    /**
     * @param int $maxRetries Retries after the first attempt.
     * @param float $delay Delay before the first retry, in seconds (doubled for each next one).
     * @param float $maxDelay Upper limit of a single delay, in seconds.
     * @param string[] $methods HTTP methods which may be retried.
     * @param (Closure(float): void)|null $sleeper Waits the given seconds (replace it in tests or async runtimes).
     */
    public function __construct(
        private readonly int $maxRetries = 2,
        private readonly float $delay = 0.5,
        private readonly float $maxDelay = 5.0,
        private readonly array $methods = [Method::GET, Method::PUT, Method::PATCH],
        ?Closure $sleeper = null,
    ) {
        $this->sleeper = $sleeper ?? static function (float $seconds): void {
            usleep((int) round($seconds * 1_000_000));
        };
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $retryable = in_array($request->getMethod(), $this->methods, true);
        $attempt = 0;

        while (true) {
            try {
                $response = $next->handle($request);
            } catch (TransportException $e) {
                if (!$retryable || $attempt >= $this->maxRetries) {
                    throw $e;
                }

                ($this->sleeper)($this->getBackoffDelay($attempt++));

                continue;
            }

            if (
                !$retryable
                || $attempt >= $this->maxRetries
                || !in_array($response->getStatusCode(), self::RETRYABLE_STATUS_CODES, true)
            ) {
                return $response;
            }

            $delay = $this->getResponseDelay($response, $attempt);

            if ($delay === null) {
                return $response;
            }

            ($this->sleeper)($delay);
            $attempt++;
        }
    }

    private function getResponseDelay(Response $response, int $attempt): ?float
    {
        $retryAfter = $response->getHeader('Retry-After');

        if ($retryAfter === null || !is_numeric($retryAfter)) {
            return $this->getBackoffDelay($attempt);
        }

        $delay = max(0.0, (float) $retryAfter);

        return $delay <= $this->maxDelay ? $delay : null;
    }

    private function getBackoffDelay(int $attempt): float
    {
        return min($this->maxDelay, $this->delay * (2 ** $attempt));
    }
}
