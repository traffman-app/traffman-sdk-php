<?php

declare(strict_types=1);

namespace Traffman\Sdk\Resource;

use Traffman\Sdk\Api\ApiClientInterface;
use Traffman\Sdk\Http\Method;
use Traffman\Sdk\Http\Operation;
use Traffman\Sdk\Model\HealthCheckResult;
use Traffman\Sdk\Model\HealthStatus;

final class HealthResource implements HealthResourceInterface
{
    public function __construct(
        private readonly ApiClientInterface $api,
    ) {
    }

    public function common(): HealthStatus
    {
        $response = $this->api->request(
            Method::GET,
            'health/common',
            authenticated: false,
            operation: Operation::HEALTH_COMMON,
        );

        return HealthStatus::fromArray($response->toArray());
    }

    public function checkConnection(string $callbackUrl): HealthCheckResult
    {
        $response = $this->api->request(
            Method::POST,
            'clients/health/connection',
            json: ['callbackUrl' => $callbackUrl],
            authenticated: false,
            operation: Operation::HEALTH_CONNECTION,
        );

        return HealthCheckResult::fromArray($response->toArray());
    }

    public function checkContentUrl(string $url): HealthCheckResult
    {
        $response = $this->api->request(
            Method::POST,
            'clients/health/content/url',
            json: ['url' => $url],
            authenticated: false,
            operation: Operation::HEALTH_CONTENT_URL,
        );

        return HealthCheckResult::fromArray($response->toArray());
    }
}
