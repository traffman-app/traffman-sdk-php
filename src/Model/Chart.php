<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use Traffman\Sdk\Internal\Data;

/**
 * Statistics chart (`GET /clients/{id}/content/statistics`).
 */
final class Chart
{
    /**
     * @param string $period `day`, `week`, `month` or `year`.
     * @param array<int|string, scalar> $valuesMap Points of the X axis (ATOM dates).
     * @param ChartDataset[] $datasets
     * @param string|null $detailsUrl Page of the detailed statistics in the Traffman account.
     *
     * @internal Build the model with {@see self::fromArray()}: the constructor arguments may change
     *           in a minor version.
     */
    public function __construct(
        public readonly string $period,
        public readonly array $valuesMap,
        public readonly array $datasets,
        public readonly ?string $detailsUrl = null,
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
        return new self(
            $data->string('period'),
            self::filterScalars($data->array('valuesMap')),
            $data->mapList('datasets', ChartDataset::fromData(...)),
            $data->nullableString('detailsUrl'),
            $data->all(),
        );
    }

    /**
     * @internal
     *
     * @param array<mixed> $values
     *
     * @return array<int|string, scalar>
     */
    public static function filterScalars(array $values): array
    {
        return array_filter($values, 'is_scalar');
    }
}
