<?php

declare(strict_types=1);

namespace Traffman\Sdk\Webhook;

use Closure;
use Traffman\Sdk\Credentials\CredentialsProviderInterface;
use Traffman\Sdk\Signature\Signer;

/**
 * Handles the calls of the API to the callback URL passed to
 * {@see \Traffman\Sdk\Resource\ClientResourceInterface::confirm()}.
 *
 * The API sends two calls, both carrying the signature of the confirmation request:
 * 1. `{"type": "validation", "token": "..."}` – answered with HTTP 200 and the signature of the token;
 * 2. `{"type": "success", "id": "<client ID>"}` – the listener stores the ID, answered with HTTP 202.
 * Anything else is answered with HTTP 400.
 *
 * Expose it on a public URL of the platform (a REST route, a controller) and send back the returned response.
 */
final class ClientConfirmWebhookHandler
{
    public const TYPE_VALIDATION = 'validation';
    public const TYPE_SUCCESS = 'success';

    private readonly ClientConfirmedListenerInterface $listener;

    /**
     * @param string $callbackUrl The same callback URL as passed to the confirmation request.
     * @param ClientConfirmedListenerInterface|(Closure(string): void) $listener
     */
    public function __construct(
        private readonly CredentialsProviderInterface $credentialsProvider,
        private readonly string $callbackUrl,
        ClientConfirmedListenerInterface|Closure $listener,
    ) {
        $this->listener = $listener instanceof Closure ? new CallbackClientConfirmedListener($listener) : $listener;
    }

    /**
     * @param string $body Raw request body.
     */
    public function handleRaw(string $body): WebhookResponse
    {
        $payload = WebhookPayload::decode($body);

        return $payload !== null ? $this->handle($payload) : new WebhookResponse(400);
    }

    /**
     * @param array<string, mixed> $payload Decoded JSON body.
     */
    public function handle(array $payload): WebhookResponse
    {
        $credentials = $this->credentialsProvider->getCredentials();
        $signature = $payload['signature'] ?? null;

        // The payload is JSON, so the signature may be of any type.
        if ($credentials === null || !is_string($signature)) {
            return new WebhookResponse(400);
        }

        $signer = new Signer($credentials->getApiSecret());

        // compared in constant time, so the response time does not reveal how much of the signature matches
        if (!hash_equals($signer->signConfirmation($credentials->getApiKey(), $this->callbackUrl), $signature)) {
            return new WebhookResponse(400);
        }

        $type = $payload['type'] ?? null;

        if ($type === self::TYPE_VALIDATION) {
            $token = $payload['token'] ?? null;

            if (!is_string($token) || $token === '') {
                return new WebhookResponse(400);
            }

            return new WebhookResponse(200, ['signature' => $signer->sign($token)]);
        }

        if ($type === self::TYPE_SUCCESS) {
            $clientId = $payload['id'] ?? null;

            if (!is_string($clientId) || !preg_match('/^[a-z0-9-]{36}$/', $clientId)) {
                return new WebhookResponse(400);
            }

            $this->listener->onClientConfirmed($clientId);

            return new WebhookResponse(202);
        }

        return new WebhookResponse(400);
    }
}
