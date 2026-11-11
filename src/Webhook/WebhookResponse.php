<?php

declare(strict_types=1);

namespace Traffman\Sdk\Webhook;

/**
 * Response the platform must send back to the API for a webhook call.
 */
final class WebhookResponse
{
    /**
     * @param array<string, mixed>|null $body JSON body (none when null).
     */
    public function __construct(
        public readonly int $statusCode,
        public readonly ?array $body = null,
    ) {
    }

    /**
     * JSON body ready to output (an empty string when there is no body).
     */
    public function getContent(): string
    {
        return $this->body !== null ? (string) json_encode($this->body, JSON_UNESCAPED_SLASHES) : '';
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->body !== null ? ['Content-Type' => 'application/json'] : [];
    }
}
