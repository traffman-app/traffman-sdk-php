<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use Traffman\Sdk\Internal\Data;

/**
 * Validation error of a field.
 */
final class Violation
{
    /**
     * @internal Build the model with {@see self::fromArray()}: the constructor arguments may change
     *           in a minor version.
     */
    public function __construct(
        /** Path of the field, e.g. `items[0].url`. */
        public readonly string $path,
        public readonly string $message,
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
            $data->string('path'),
            $data->string('message'),
            $data->all(),
        );
    }
}
