<?php

declare(strict_types=1);

namespace Traffman\Sdk\Credentials;

use SensitiveParameter;
use Traffman\Sdk\Exception\InvalidArgumentException;
use Traffman\Sdk\Exception\MissingCredentialsException;

/**
 * API credentials of a client (a site connected to Traffman).
 *
 * The key and the secret are issued in the Traffman account. The client ID is received after the client
 * confirmation (see {@see \Traffman\Sdk\Webhook\ClientConfirmWebhookHandler}), so it is optional until then.
 */
final class Credentials
{
    public function __construct(
        private readonly string $apiKey,
        #[SensitiveParameter]
        private readonly string $apiSecret,
        private readonly ?string $clientId = null,
    ) {
        if ($apiKey === '' || !preg_match('/^[a-zA-Z0-9]+$/', $apiKey)) {
            throw new InvalidArgumentException('The API key must be a non-empty alphanumeric string.');
        }

        if ($apiSecret === '') {
            throw new InvalidArgumentException('The API secret must not be empty.');
        }

        if ($clientId !== null && !preg_match('/^[a-z0-9-]{36}$/', $clientId)) {
            throw new InvalidArgumentException('The client ID must be a UUID (36 characters).');
        }
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getApiSecret(): string
    {
        return $this->apiSecret;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    /**
     * @throws MissingCredentialsException When the client is not confirmed yet.
     */
    public function requireClientId(): string
    {
        return $this->clientId ?? throw new MissingCredentialsException(
            'The client ID is not set: confirm the client first (see ClientResource::confirm()).',
        );
    }

    public function withClientId(?string $clientId): self
    {
        return new self($this->apiKey, $this->apiSecret, $clientId);
    }

    /**
     * @return array<string, string|null>
     */
    public function __debugInfo(): array
    {
        return [
            'apiKey' => $this->apiKey,
            'apiSecret' => '***',
            'clientId' => $this->clientId,
        ];
    }
}
