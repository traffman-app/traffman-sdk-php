<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use DateTimeImmutable;
use Traffman\Sdk\Internal\Data;

/**
 * State of the Traffman content block on the client site.
 *
 * | State                                   | `idle` | `lastRequestedAt` |
 * |-----------------------------------------|--------|-------------------|
 * | Not placed yet                          | false  | null              |
 * | Works                                   | false  | date              |
 * | Stopped working (no requests for 72 h)  | true   | date              |
 */
final class ContentBlock
{
    /**
     * @internal Build the model with {@see self::fromArray()}: the constructor arguments may change
     *           in a minor version.
     */
    public function __construct(
        /**
         * The block requested content before but has not requested it for the last 72 hours: it was removed,
         * the plugin was disabled or something broke. Always false for a block that never requested content.
         */
        public readonly bool $idle,
        /** Time of the last content request of the block; null when the block has never requested content (not placed yet). */
        public readonly ?DateTimeImmutable $lastRequestedAt,
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
            $data->bool('idle'),
            $data->nullableDateTime('lastRequestedAt'),
            $data->all(),
        );
    }
}
