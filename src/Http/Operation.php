<?php

declare(strict_types=1);

namespace Traffman\Sdk\Http;

/**
 * Names of the API operations (the value of the {@see RequestAttribute::OPERATION} attribute).
 *
 * Middleware can rely on them, e.g. to choose which responses are cached.
 */
final class Operation
{
    public const HEALTH_COMMON = 'health.common';
    public const HEALTH_CONNECTION = 'health.connection';
    public const HEALTH_CONTENT_URL = 'health.content_url';

    public const CLIENT_CONFIRM = 'client.confirm';
    public const CLIENT_IAM = 'client.iam';
    public const CLIENT_DASHBOARD = 'client.dashboard';
    public const CLIENT_SETTINGS_UPDATE = 'client.settings_update';

    public const CONTENT_PUSH = 'content.push';
    public const CONTENT_PUSHED_EXTERNAL_IDS = 'content.pushed_external_ids';
    public const CONTENT_STATISTICS = 'content.statistics';
    public const CONTENT_TARGETED = 'content.targeted';

    /**
     * A request sent with {@see \Traffman\Sdk\Api\ApiClientInterface::request()} without an operation name.
     */
    public const CUSTOM = 'custom';

    private function __construct()
    {
    }
}
