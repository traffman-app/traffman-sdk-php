<?php

declare(strict_types=1);

namespace Traffman\Sdk\Resource;

use Traffman\Sdk\Enum\ClientType;
use Traffman\Sdk\Exception\TraffmanExceptionInterface;
use Traffman\Sdk\Model\ClientIam;
use Traffman\Sdk\Model\ConfirmationResult;
use Traffman\Sdk\Model\Dashboard;
use Traffman\Sdk\Model\SettingsUpdate;

/**
 * The client – a site connected to Traffman.
 */
interface ClientResourceInterface
{
    /**
     * Requests the confirmation of the client for the host of the callback URL.
     *
     * The API then calls the callback URL twice: to validate it (`type: validation`) and to pass the client ID
     * (`type: success`). Handle both with {@see \Traffman\Sdk\Webhook\ClientConfirmWebhookHandler}, configured
     * with the same callback URL. Needs the API key and secret only.
     *
     * Errors: {@see \Traffman\Sdk\Exception\BadRequestException} with the codes
     * {@see \Traffman\Sdk\Exception\ErrorCode}`::CONFIRM_*`, {@see \Traffman\Sdk\Exception\RateLimitException}.
     *
     * @throws TraffmanExceptionInterface
     */
    public function confirm(string $callbackUrl): ConfirmationResult;

    /**
     * Information about the client and its subscription.
     *
     * @param ClientType|string|null $type Integration type, to get the required/preferable integration version.
     *
     * @throws TraffmanExceptionInterface
     */
    public function iam(ClientType|string|null $type = null): ClientIam;

    /**
     * @throws TraffmanExceptionInterface
     */
    public function dashboard(): Dashboard;

    /**
     * @throws TraffmanExceptionInterface
     */
    public function updateSettings(SettingsUpdate $settings): void;
}
