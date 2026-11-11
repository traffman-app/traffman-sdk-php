<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use Traffman\Sdk\Internal\Data;

/**
 * Points balance of the client.
 */
final class Points
{
    /**
     * @internal Build the model with {@see self::fromArray()}: the constructor arguments may change
     *           in a minor version.
     */
    public function __construct(
        public readonly float $replenished,
        public readonly float $earned,
        public readonly float $spent,
        public readonly float $total,
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
            $data->float('replenished'),
            $data->float('earned'),
            $data->float('spent'),
            $data->float('total'),
            $data->all(),
        );
    }
}
