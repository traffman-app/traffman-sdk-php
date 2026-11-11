<?php

declare(strict_types=1);

namespace Traffman\Sdk\Exception;

use RuntimeException;
use Traffman\Sdk\Http\Response;

/**
 * The API responded with an error status code (4xx/5xx).
 *
 * The more specific subclasses are thrown for the known statuses, so a caller may catch either the base class
 * or e.g. {@see RateLimitException} only.
 */
class ApiException extends RuntimeException implements TraffmanExceptionInterface
{
    /**
     * @param array<mixed> $payload Decoded response body (empty when it is not JSON).
     */
    public function __construct(
        string $message,
        private readonly Response $response,
        private readonly int|string|null $errorCode = null,
        private readonly array $payload = [],
    ) {
        parent::__construct($message, $response->getStatusCode());
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * The `code` field of the error response, which distinguishes errors of the same status
     * (see {@see ErrorCode}).
     */
    public function getErrorCode(): int|string|null
    {
        return $this->errorCode;
    }

    /**
     * @return array<mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
