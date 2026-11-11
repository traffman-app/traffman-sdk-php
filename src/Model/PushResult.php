<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use Traffman\Sdk\Internal\Data;

/**
 * Result of a content push (`PUT /clients/{id}/content/push`).
 */
final class PushResult
{
    /**
     * @internal Build the model with {@see self::fromArray()}: the constructor arguments may change
     *           in a minor version.
     */
    public function __construct(
        public readonly PushCounts $counts,
        /** @var PushFailure[] */
        public readonly array $failed,
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
            PushCounts::fromData($data->object('counts')),
            $data->mapList('failed', PushFailure::fromData(...)),
            $data->all(),
        );
    }
}
