<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use DateTimeImmutable;
use Traffman\Sdk\Internal\Data;

/**
 * Subscription of the account the client belongs to.
 */
final class Subscription
{
    /**
     * @internal Build the model with {@see self::fromArray()}: the constructor arguments may change
     *           in a minor version.
     */
    public function __construct(
        /** `trial`, `basic`, `standard`, `pro`, `premium`, `ambassador` or null (none). */
        public readonly ?string $type,
        public readonly ?DateTimeImmutable $expiresAt,
        public readonly ?SubscriptionRenewal $renewal,
        /** The renewal payment has failed and is retried; the payment method should be updated. */
        public readonly bool $paymentFailed,
        /** The subscription is canceled at the end of the paid period and can be resumed. */
        public readonly bool $resumable,
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
            $data->nullableString('type'),
            $data->nullableDateTime('expiresAt'),
            ($renewal = $data->nullableObject('renewal')) !== null ? SubscriptionRenewal::fromData($renewal) : null,
            $data->bool('paymentFailed'),
            $data->bool('resumable'),
            $data->all(),
        );
    }
}
