<?php

declare(strict_types=1);

namespace Traffman\Sdk\Http;

use JsonException;
use Traffman\Sdk\Exception\UnexpectedResponseException;

/**
 * Immutable HTTP response of the SDK.
 */
final class Response
{
    /**
     * @var array<string, string> Header values keyed by the lower-case name.
     */
    private array $headers = [];

    /**
     * @param array<string, string|string[]> $headers Several values of a header are joined with a comma.
     */
    public function __construct(
        private readonly int $statusCode,
        array $headers = [],
        private readonly string $body = '',
    ) {
        foreach ($headers as $name => $value) {
            $this->headers[strtolower($name)] = is_array($value) ? implode(', ', $value) : $value;
        }
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * @return array<string, string> Header values keyed by the lower-case name.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Decodes the JSON body. An empty body is decoded as an empty array.
     *
     * @return array<mixed>
     *
     * @throws UnexpectedResponseException When the body is not a JSON object or array.
     */
    public function toArray(): array
    {
        if (trim($this->body) === '') {
            return [];
        }

        try {
            $data = json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new UnexpectedResponseException('The response body is not a valid JSON: ' . $e->getMessage(), $e);
        }

        if (!is_array($data)) {
            throw new UnexpectedResponseException('The response body is not a JSON object or array.');
        }

        return $data;
    }
}
