<?php

declare(strict_types=1);

namespace Traffman\Sdk\Http;

/**
 * Immutable HTTP request of the SDK.
 *
 * It is deliberately independent of PSR-7, so a transport can be written for any platform
 * (WordPress HTTP API, Drupal/Guzzle, Laravel, plain cURL) without extra packages.
 *
 * Attributes are not sent: they carry metadata for the middleware (operation name, authentication flag, etc.).
 */
final class Request
{
    /**
     * @var array<string, string> Header values keyed by the original name.
     */
    private array $headers = [];

    /**
     * @var array<string, mixed>
     */
    private array $attributes;

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private string $method,
        private string $url,
        array $headers = [],
        private string $body = '',
        array $attributes = [],
    ) {
        $this->method = strtoupper($method);

        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

        $this->attributes = $attributes;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function withUrl(string $url): self
    {
        $clone = clone $this;
        $clone->url = $url;

        return $clone;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return $this->findHeaderName($name) !== null;
    }

    public function getHeader(string $name): ?string
    {
        $originalName = $this->findHeaderName($name);

        return $originalName !== null ? $this->headers[$originalName] : null;
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->setHeader($name, $value);

        return $clone;
    }

    public function withoutHeader(string $name): self
    {
        $originalName = $this->findHeaderName($name);

        if ($originalName === null) {
            return $this;
        }

        $clone = clone $this;
        unset($clone->headers[$originalName]);

        return $clone;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function withBody(string $body): self
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    public function withAttribute(string $name, mixed $value): self
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    private function setHeader(string $name, string $value): void
    {
        $originalName = $this->findHeaderName($name);

        if ($originalName !== null) {
            unset($this->headers[$originalName]);
        }

        $this->headers[$name] = $value;
    }

    private function findHeaderName(string $name): ?string
    {
        foreach (array_keys($this->headers) as $headerName) {
            if (strcasecmp($headerName, $name) === 0) {
                return $headerName;
            }
        }

        return null;
    }
}
