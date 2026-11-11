<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use DateTimeImmutable;
use Traffman\Sdk\Internal\Data;

/**
 * Information about the authenticated client (`GET /clients/iam`).
 */
final class ClientIam
{
    /**
     * @internal Build the model with {@see self::fromArray()}: the constructor arguments may change
     *           in a minor version.
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $description,
        public readonly Subscription $subscription,
        public readonly DateTimeImmutable $createdAt,
        /** Set when the client type was passed to the request. */
        public readonly ?ClientVersion $version,
        /** @var array<mixed> All the fields of the response data, including the ones the SDK does not map. */
        public readonly array $raw = [],
    ) {
    }

    /**
     * @param array<mixed> $data Decoded API response.
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
        return new self(
            $data->string('id'),
            $data->nullableString('description'),
            Subscription::fromData($data->object('subscription')),
            $data->dateTime('createdAt'),
            ($version = $data->nullableObject('version')) !== null ? ClientVersion::fromData($version) : null,
            $data->all(),
        );
    }
}
