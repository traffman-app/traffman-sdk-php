<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use Traffman\Sdk\Internal\Data;

/**
 * Counts of the processed content items.
 */
final class PushCounts
{
    /**
     * @internal Build the model with {@see self::fromArray()}: the constructor arguments may change
     *           in a minor version.
     */
    public function __construct(
        public readonly int $total,
        public readonly int $inserted,
        public readonly int $updated,
        public readonly int $removed,
        public readonly int $skipped,
        public readonly int $failed,
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
            $data->int('inserted'),
            $data->int('updated'),
            $data->int('removed'),
            $data->int('skipped'),
            $data->int('failed'),
            $data->all(),
        );
    }
}
