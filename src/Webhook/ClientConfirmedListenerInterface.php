<?php

declare(strict_types=1);

namespace Traffman\Sdk\Webhook;

/**
 * Receives the client ID when the API confirms the client.
 */
interface ClientConfirmedListenerInterface
{
    /**
     * Store the client ID (e.g. next to the API key and secret): it is required by most of the API calls.
     */
    public function onClientConfirmed(string $clientId): void;
}
