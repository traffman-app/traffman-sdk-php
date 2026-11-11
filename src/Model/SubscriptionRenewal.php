<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use DateTimeImmutable;
use Traffman\Sdk\Internal\Data;

/**
 * Next renewal of the subscription.
 */
final class SubscriptionRenewal
{
    /**
     * @internal Build the model with {@see self::fromArray()}: the constructor arguments may change
     *           in a minor version.
     */
    public function __construct(
        /** `month` or `year`. */
        public readonly string $interval,
        public readonly DateTimeImmutable $renewsAt,
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
            $data->string('interval'),
            $data->dateTime('renewsAt'),
            $data->all(),
        );
    }
}
