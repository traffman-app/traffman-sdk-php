<?php

declare(strict_types=1);

namespace Example\WordPress;

use Traffman\Sdk\Credentials\Credentials;
use Traffman\Sdk\Credentials\CredentialsProviderInterface;
use Traffman\Sdk\Webhook\ClientConfirmedListenerInterface;

/**
 * Reads the credentials from the WordPress options on every request and stores the client ID
 * received by the confirmation webhook.
 */
final class WpOptionsCredentialsProvider implements CredentialsProviderInterface, ClientConfirmedListenerInterface
{
    public function getCredentials(): ?Credentials
    {
        $key = (string) get_option('traffman_api-key', '');
        $secret = (string) get_option('traffman_api-secret', '');
        $clientId = (string) get_option('traffman_api-client-id', '');

        if ($key === '' || $secret === '') {
            return null;
        }

        return new Credentials($key, $secret, $clientId !== '' ? $clientId : null);
    }

    public function onClientConfirmed(string $clientId): void
    {
        update_option('traffman_api-client-id', $clientId);
    }
}
