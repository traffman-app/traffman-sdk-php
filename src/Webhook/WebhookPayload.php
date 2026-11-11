<?php

declare(strict_types=1);

namespace Traffman\Sdk\Webhook;

/**
 * Decodes the raw body of a webhook call.
 */
final class WebhookPayload
{
    /**
     * @return array<string, mixed>|null Null when the body is not a JSON object.
     */
    public static function decode(string $body): ?array
    {
        $data = json_decode($body, true);

        if (!is_array($data) || ($data !== [] && array_is_list($data))) {
            return null;
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    private function __construct()
    {
    }
}
