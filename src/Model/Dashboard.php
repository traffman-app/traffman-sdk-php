<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use Traffman\Sdk\Internal\Data;

/**
 * Dashboard of the client (`GET /clients/{id}/dashboard`).
 */
final class Dashboard
{
    /**
     * @internal Build the model with {@see self::fromArray()}: the constructor arguments may change
     *           in a minor version.
     */
    public function __construct(
        /** @var Notice[] */
        public readonly array $notices,
        public readonly Points $points,
        public readonly ContentCount $content,
        /** @var Achievement[] */
        public readonly array $achievements,
        /** @var News[] */
        public readonly array $news,
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
            $data->mapList('notices', Notice::fromData(...)),
            Points::fromData($data->object('points')),
            ContentCount::fromData($data->object('content')),
            $data->mapList('achievements', Achievement::fromData(...)),
            $data->mapList('news', News::fromData(...)),
            $data->all(),
        );
    }
}
