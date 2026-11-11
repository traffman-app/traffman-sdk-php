<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use Traffman\Sdk\Internal\Data;

/**
 * Content targeted to the site visitor (`GET /clients/{id}/content/targeted/{limit}`).
 */
final class TargetedContent
{
    /**
     * @internal Build the model with {@see self::fromArray()}: the constructor arguments may change
     *           in a minor version.
     */
    public function __construct(
        /** @var TargetedContentItem[] */
        public readonly array $items,
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
            $data->mapList('items', TargetedContentItem::fromData(...)),
            $data->all(),
        );
    }
}
