<?php

declare(strict_types=1);

namespace Traffman\Sdk\Resource;

use Traffman\Sdk\Api\ApiClientInterface;
use Traffman\Sdk\Enum\ClientType;
use Traffman\Sdk\Http\Method;
use Traffman\Sdk\Http\Operation;
use Traffman\Sdk\Model\ClientIam;
use Traffman\Sdk\Model\ConfirmationResult;
use Traffman\Sdk\Model\Dashboard;
use Traffman\Sdk\Model\SettingsUpdate;
use Traffman\Sdk\Signature\Signer;

final class ClientResource implements ClientResourceInterface
{
    public function __construct(
        private readonly ApiClientInterface $api,
    ) {
    }

    public function confirm(string $callbackUrl): ConfirmationResult
    {
        $credentials = $this->api->requireCredentials();
        $signer = new Signer($credentials->getApiSecret());

        $response = $this->api->request(
            Method::POST,
            'clients/confirm',
            json: [
                'key' => $credentials->getApiKey(),
                'callbackUrl' => $callbackUrl,
                'signature' => $signer->signConfirmation($credentials->getApiKey(), $callbackUrl),
            ],
            authenticated: false,
            operation: Operation::CLIENT_CONFIRM,
        );

        return ConfirmationResult::fromArray($response->toArray());
    }

    public function iam(ClientType|string|null $type = null): ClientIam
    {
        $response = $this->api->request(
            Method::GET,
            'clients/iam',
            query: ['type' => $type instanceof ClientType ? $type->value : $type],
            operation: Operation::CLIENT_IAM,
        );

        return ClientIam::fromArray($response->toArray());
    }

    public function dashboard(): Dashboard
    {
        $response = $this->api->request(
            Method::GET,
            sprintf('clients/%s/dashboard', $this->api->requireCredentials()->requireClientId()),
            operation: Operation::CLIENT_DASHBOARD,
        );

        return Dashboard::fromArray($response->toArray());
    }

    public function updateSettings(SettingsUpdate $settings): void
    {
        $this->api->request(
            Method::PATCH,
            sprintf('clients/%s/settings', $this->api->requireCredentials()->requireClientId()),
            json: $settings->toArray(),
            operation: Operation::CLIENT_SETTINGS_UPDATE,
        );
    }
}
