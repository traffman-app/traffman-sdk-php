<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use Traffman\Sdk\Internal\Data;

/**
 * Counts of the content pushed by the client, by status.
 */
final class ContentCount
{
    /**
     * @internal Build the model with {@see self::fromArray()}: the constructor arguments may change
     *           in a minor version.
     */
    public function __construct(
        public readonly int $total,
        public readonly int $queued,
        public readonly int $valid,
        public readonly int $invalid,
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
            $data->int('total'),
            $data->int('queued'),
            $data->int('valid'),
            $data->int('invalid'),
            $data->all(),
        );
    }
}
