<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use DateTimeImmutable;
use Traffman\Sdk\Internal\Data;

/**
 * News article of Traffman.
 */
final class News
{
    /**
     * @internal Build the model with {@see self::fromArray()}: the constructor arguments may change
     *           in a minor version.
     */
    public function __construct(
        public readonly string $title,
        public readonly string $excerpt,
        public readonly string $url,
        public readonly DateTimeImmutable $createdAt,
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
            $data->string('title'),
            $data->string('excerpt'),
            $data->string('url'),
            $data->dateTime('createdAt'),
            $data->all(),
        );
    }
}
