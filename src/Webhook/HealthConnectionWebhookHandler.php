<?php

declare(strict_types=1);

namespace Traffman\Sdk\Webhook;

/**
 * Handles the call of the API to the URL passed to
 * {@see \Traffman\Sdk\Resource\HealthResourceInterface::checkConnection()}: echoes the received token.
 */
final class HealthConnectionWebhookHandler
{
    public function handleRaw(string $body): WebhookResponse
    {
        return $this->handle(WebhookPayload::decode($body) ?? []);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload): WebhookResponse
    {
        $token = $payload['token'] ?? null;

        return new WebhookResponse(200, ['token' => is_string($token) ? $token : null]);
    }
}
