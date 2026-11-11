<?php

declare(strict_types=1);

namespace Traffman\Sdk\Resource;

use Traffman\Sdk\Exception\TraffmanExceptionInterface;
use Traffman\Sdk\Model\HealthCheckResult;
use Traffman\Sdk\Model\HealthStatus;

/**
 * Health checks. They need no credentials, so they can be used before the site is connected.
 */
interface HealthResourceInterface
{
    /**
     * Checks that the API is reachable.
     *
     * @throws TraffmanExceptionInterface
     */
    public function common(): HealthStatus;

    /**
     * Asks the API to call the site back: the API posts `{"token": "..."}` to the URL and expects the same
     * token in the response (see {@see \Traffman\Sdk\Webhook\HealthConnectionWebhookHandler}).
     *
     * @throws TraffmanExceptionInterface
     */
    public function checkConnection(string $callbackUrl): HealthCheckResult;

    /**
     * Asks the API to open a page of the site: it must respond with HTTP 200 and an HTML body.
     *
     * @throws TraffmanExceptionInterface
     */
    public function checkContentUrl(string $url): HealthCheckResult;
}
