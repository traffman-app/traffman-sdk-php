<?php

declare(strict_types=1);

namespace Traffman\Sdk\Resource;

use Traffman\Sdk\Enum\StatisticPeriod;
use Traffman\Sdk\Enum\StatisticType;
use Traffman\Sdk\Exception\TraffmanExceptionInterface;
use Traffman\Sdk\Model\Chart;
use Traffman\Sdk\Model\ContentItem;
use Traffman\Sdk\Model\PushResult;
use Traffman\Sdk\Model\TargetedContent;

/**
 * Content of the exchange: the site's own content pushed to the network and the content of others shown on it.
 */
interface ContentResourceInterface
{
    /**
     * Max items in one push request.
     */
    public const PUSH_MAX_ITEMS = 100;

    public const TARGETED_MIN_LIMIT = 1;
    public const TARGETED_MAX_LIMIT = 4;

    /**
     * Synchronizes the content of the site with the exchange.
     *
     * WARNING: `$mapIds` is the FULL list of the external IDs which must exist in the exchange – the API removes
     * every pushed item missing from it. An empty list removes all the content of the site.
     *
     * @param string[] $mapIds External IDs of all the items selected for the exchange.
     * @param ContentItem[] $items Items to create or update (at most {@see self::PUSH_MAX_ITEMS}).
     *
     * @return PushResult Counts and the items rejected by the validation.
     *
     * @throws TraffmanExceptionInterface
     */
    public function push(array $mapIds, array $items): PushResult;

    /**
     * Pushes any number of items in batches of {@see self::PUSH_MAX_ITEMS}, each with the full `$mapIds`.
     *
     * @param string[] $mapIds
     * @param iterable<ContentItem> $items
     *
     * @return PushResult[] Result of each batch (one result when there are no items).
     *
     * @throws TraffmanExceptionInterface
     */
    public function pushInBatches(array $mapIds, iterable $items, int $batchSize = self::PUSH_MAX_ITEMS): array;

    /**
     * External IDs of all the items pushed by the client, whatever their status (queued included).
     *
     * @return string[]
     *
     * @throws TraffmanExceptionInterface
     */
    public function pushedExternalIds(): array;

    /**
     * @param string|null $timezone IANA timezone of the chart dates, e.g. `Europe/Kyiv` (UTC when null).
     *
     * @throws TraffmanExceptionInterface
     */
    public function statistics(
        StatisticType $type = StatisticType::CLIENT,
        StatisticPeriod $period = StatisticPeriod::WEEK,
        ?string $timezone = null,
    ): Chart;

    /**
     * Content of other sites to show to the current visitor.
     *
     * Call it on the visitor's request only: the API counts every call and needs the visitor IP
     * (from the configured user IP resolver, or `$userIp`).
     *
     * @param int $limit Number of items, {@see self::TARGETED_MIN_LIMIT}–{@see self::TARGETED_MAX_LIMIT}.
     * @param string|null $language Preferred language of the content (ISO 639-1).
     * @param string|null $userIp Visitor IP; overrides the user IP resolver.
     *
     * @throws TraffmanExceptionInterface
     */
    public function targeted(int $limit, ?string $language = null, ?string $userIp = null): TargetedContent;
}
