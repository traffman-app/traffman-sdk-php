<?php

declare(strict_types=1);

namespace Traffman\Sdk\Resource;

use Traffman\Sdk\Api\ApiClientInterface;
use Traffman\Sdk\Enum\StatisticPeriod;
use Traffman\Sdk\Enum\StatisticType;
use Traffman\Sdk\Exception\InvalidArgumentException;
use Traffman\Sdk\Http\Method;
use Traffman\Sdk\Http\Operation;
use Traffman\Sdk\Http\RequestAttribute;
use Traffman\Sdk\Internal\Data;
use Traffman\Sdk\Model\Chart;
use Traffman\Sdk\Model\ContentItem;
use Traffman\Sdk\Model\PushResult;
use Traffman\Sdk\Model\TargetedContent;
use Traffman\Sdk\UserIp\UserIpResolverInterface;

final class ContentResource implements ContentResourceInterface
{
    private const HEADER_TIMEZONE = 'X-Timezone';

    public function __construct(
        private readonly ApiClientInterface $api,
        private readonly UserIpResolverInterface $userIpResolver,
    ) {
    }

    public function push(array $mapIds, array $items): PushResult
    {
        if (count($items) > self::PUSH_MAX_ITEMS) {
            throw new InvalidArgumentException(sprintf(
                'At most %d content items can be pushed at once, %d given (use pushInBatches()).',
                self::PUSH_MAX_ITEMS,
                count($items),
            ));
        }

        $itemsData = [];

        foreach ($items as $item) {
            if (!$item instanceof ContentItem) {
                throw new InvalidArgumentException('Every pushed item must be an instance of ContentItem.');
            }

            $itemsData[] = $item->toArray();
        }

        $response = $this->api->request(
            Method::PUT,
            sprintf('clients/%s/content/push', $this->api->requireCredentials()->requireClientId()),
            json: [
                'mapIds' => array_values(array_unique(array_map('strval', $mapIds))),
                'items' => $itemsData,
            ],
            operation: Operation::CONTENT_PUSH,
        );

        return PushResult::fromArray($response->toArray());
    }

    public function pushInBatches(array $mapIds, iterable $items, int $batchSize = self::PUSH_MAX_ITEMS): array
    {
        if ($batchSize < 1 || $batchSize > self::PUSH_MAX_ITEMS) {
            throw new InvalidArgumentException(
                sprintf('The batch size must be between 1 and %d.', self::PUSH_MAX_ITEMS),
            );
        }

        $results = [];
        $batch = [];

        foreach ($items as $item) {
            $batch[] = $item;

            if (count($batch) === $batchSize) {
                $results[] = $this->push($mapIds, $batch);
                $batch = [];
            }
        }

        if ($batch !== [] || $results === []) {
            $results[] = $this->push($mapIds, $batch);
        }

        return $results;
    }

    public function pushedExternalIds(): array
    {
        $response = $this->api->request(
            Method::GET,
            sprintf('clients/%s/content/pushed/external-ids', $this->api->requireCredentials()->requireClientId()),
            operation: Operation::CONTENT_PUSHED_EXTERNAL_IDS,
        );

        return (new Data($response->toArray()))->stringList('items');
    }

    public function statistics(
        StatisticType $type = StatisticType::CLIENT,
        StatisticPeriod $period = StatisticPeriod::WEEK,
        ?string $timezone = null,
    ): Chart {
        $response = $this->api->request(
            Method::GET,
            sprintf('clients/%s/content/statistics', $this->api->requireCredentials()->requireClientId()),
            query: ['type' => $type->value, 'period' => $period->value],
            operation: Operation::CONTENT_STATISTICS,
            headers: $timezone !== null ? [self::HEADER_TIMEZONE => $timezone] : [],
        );

        return Chart::fromArray($response->toArray());
    }

    public function targeted(int $limit, ?string $language = null, ?string $userIp = null): TargetedContent
    {
        if ($limit < self::TARGETED_MIN_LIMIT || $limit > self::TARGETED_MAX_LIMIT) {
            throw new InvalidArgumentException(sprintf(
                'The limit must be between %d and %d, %d given.',
                self::TARGETED_MIN_LIMIT,
                self::TARGETED_MAX_LIMIT,
                $limit,
            ));
        }

        $userIp ??= $this->userIpResolver->resolve();

        if ($userIp === null || filter_var($userIp, FILTER_VALIDATE_IP) === false) {
            throw new InvalidArgumentException(
                'The targeted content requires a valid IP address of the site visitor: '
                . 'pass it explicitly or configure a user IP resolver.',
            );
        }

        $response = $this->api->request(
            Method::GET,
            sprintf(
                'clients/%s/content/targeted/%d',
                $this->api->requireCredentials()->requireClientId(),
                $limit,
            ),
            query: ['language' => $language],
            operation: Operation::CONTENT_TARGETED,
            attributes: [RequestAttribute::USER_IP => $userIp],
        );

        return TargetedContent::fromArray($response->toArray());
    }
}
