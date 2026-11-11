<?php

declare(strict_types=1);

namespace Traffman\Sdk\Exception;

use Traffman\Sdk\Http\Response;

/**
 * HTTP 429 – too many requests.
 */
class RateLimitException extends ApiException
{
    /**
     * @param array<mixed> $payload
     */
    public function __construct(
        string $message,
        Response $response,
        int|string|null $errorCode,
        array $payload,
        private readonly ?int $retryAfter,
    ) {
        parent::__construct($message, $response, $errorCode, $payload);
    }

    /**
     * Seconds to wait before the next attempt (the `Retry-After` header), if known.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
