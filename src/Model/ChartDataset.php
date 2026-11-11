<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use Traffman\Sdk\Exception\UnexpectedResponseException;
use Traffman\Sdk\Internal\Data;

/**
 * Data series of a chart.
 */
final class ChartDataset
{
    /**
     * @param string|int $name Metric type of the series, or `ctr` (click-through rate, %).
     * @param array<int|string, scalar> $values Values keyed the same as {@see Chart::$valuesMap}.
     *
     * @internal Build the model with {@see self::fromArray()}: the constructor arguments may change
     *           in a minor version.
     */
    public function __construct(
        public readonly string|int $name,
        public readonly array $values,
        /** @var array<mixed> All the fields of the response data, including the ones the SDK does not map. */
        public readonly array $raw = [],
    ) {
    }

    /**
     * @param array<mixed> $data
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
        $name = $data->all()['name'] ?? null;

        if (!is_string($name) && !is_int($name)) {
            throw new UnexpectedResponseException('The response field "name" of a chart dataset is invalid.');
        }

        return new self($name, Chart::filterScalars($data->array('values')), $data->all());
    }
}
