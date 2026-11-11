<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use Traffman\Sdk\Internal\Data;

/**
 * Result of the client confirmation request (`POST /clients/confirm`).
 *
 * When the request is accepted, the API confirms the client asynchronously by calling the callback URL
 * (see {@see \Traffman\Sdk\Webhook\ClientConfirmWebhookHandler}), which receives the client ID.
 */
final class ConfirmationResult
{
    /**
     * @internal Build the model with {@see self::fromArray()}: the constructor arguments may change
     *           in a minor version.
     */
    public function __construct(
        /** The client was confirmed earlier for the same host; {@see self::$clientId} is set then. */
        public readonly bool $alreadyConfirmed,
        public readonly string $message,
        public readonly ?string $clientId,
        /** @var array<mixed> All the fields of the response data, including the ones the SDK does not map. */
        public readonly array $raw = [],
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return self::fromData(new Data($data));
    }

    /**
     * @internal
     */
    public static function fromData(Data $data): self
    {
        $clientId = $data->nullableString('id');

        return new self($clientId !== null, $data->string('message'), $clientId, $data->all());
    }
}
