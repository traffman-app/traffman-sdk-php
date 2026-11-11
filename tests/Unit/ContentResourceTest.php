<?php

declare(strict_types=1);

namespace Traffman\Sdk\Tests\Unit;

use DateTimeImmutable;
use Traffman\Sdk\Enum\StatisticPeriod;
use Traffman\Sdk\Enum\StatisticType;
use Traffman\Sdk\Exception\InvalidArgumentException;
use Traffman\Sdk\Model\ContentItem;
use Traffman\Sdk\Testing\MockTransport;
use Traffman\Sdk\Tests\Support\Fixtures;
use Traffman\Sdk\Tests\Support\ServerSignature;
use Traffman\Sdk\Tests\Support\TestCase;

final class ContentResourceTest extends TestCase
{
    public function testPush(): void
    {
        $this->transport->append(MockTransport::json(Fixtures::pushResult()));

        $result = $this->createClient()->content()->push(
            ['1', '2', '3', '1'],
            [
                self::item('1'),
                self::item('2'),
            ],
        );

        $request = $this->transport->getLastRequest();
        self::assertNotNull($request);
        self::assertSame('PUT', $request->getMethod());
        self::assertSame(
            'https://api.traffman.com/v1/clients/' . self::CLIENT_ID . '/content/push',
            $request->getUrl(),
        );
        self::assertTrue(ServerSignature::isRequestValid($request, self::API_SECRET));

        $body = self::decodeBody($request->getBody());
        self::assertSame(['1', '2', '3'], $body['mapIds']);
        self::assertSame(
            [
                'id' => '1',
                'url' => 'https://example.com/post-1',
                'language' => 'uk',
                'title' => 'Заголовок',
                'content' => str_repeat('Текст ', 30),
                'updatedAt' => '2026-01-01T10:00:00+02:00',
            ],
            self::list($body['items'])[0],
        );
        self::assertArrayNotHasKey('_env', $body);
        // unicode is sent as is, not escaped
        self::assertStringContainsString('Заголовок', $request->getBody());

        self::assertSame(1, $result->counts->inserted);
        self::assertSame('2', $result->failed[0]->id);
        self::assertSame('content', $result->failed[0]->violations[0]->path);
    }

    public function testPushRejectsTooManyItems(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createClient()->content()->push([], array_map(
            static fn(int $id): ContentItem => self::item((string) $id),
            range(1, 101),
        ));
    }

    public function testPushInBatches(): void
    {
        $this->transport
            ->append(MockTransport::json(Fixtures::pushResult()))
            ->append(MockTransport::json(Fixtures::pushResult()))
            ->append(MockTransport::json(Fixtures::pushResult()));

        $ids = array_map('strval', range(1, 5));
        $items = (static function () use ($ids): \Generator {
            foreach ($ids as $id) {
                yield self::item($id);
            }
        })();

        $results = $this->createClient()->content()->pushInBatches($ids, $items, 2);

        self::assertCount(3, $results);

        foreach ($this->transport->getRequests() as $request) {
            self::assertSame($ids, self::decodeBody($request->getBody())['mapIds']);
        }

        self::assertCount(1, self::list(self::decodeBody($this->transport->getRequests()[2]->getBody())['items']));
    }

    public function testPushInBatchesWithoutItemsStillSyncsTheMap(): void
    {
        $this->transport->append(MockTransport::json(Fixtures::pushResult()));

        $results = $this->createClient()->content()->pushInBatches(['1'], []);

        self::assertCount(1, $results);
        self::assertSame([], self::decodeBody($this->transport->getRequests()[0]->getBody())['items']);
    }

    public function testPushedExternalIds(): void
    {
        $this->transport->append(MockTransport::json(['items' => ['1', '5']]));

        self::assertSame(['1', '5'], $this->createClient()->content()->pushedExternalIds());
        self::assertStringEndsWith(
            '/content/pushed/external-ids',
            (string) $this->transport->getLastRequest()?->getUrl(),
        );
    }

    public function testStatistics(): void
    {
        $this->transport->append(MockTransport::json(Fixtures::chart()));

        $chart = $this->createClient()->content()->statistics(
            StatisticType::CONTENT,
            StatisticPeriod::MONTH,
            'Europe/Kyiv',
        );

        $request = $this->transport->getLastRequest();
        self::assertNotNull($request);
        self::assertStringEndsWith('/content/statistics?type=content&period=month', $request->getUrl());
        self::assertSame('Europe/Kyiv', $request->getHeader('X-Timezone'));
        self::assertTrue(ServerSignature::isRequestValid($request, self::API_SECRET));
        self::assertSame('week', $chart->period);
        self::assertCount(3, $chart->datasets);
        self::assertSame('ctr', $chart->datasets[2]->name);
        self::assertSame([1, 4], $chart->datasets[1]->values);
        self::assertSame('https://traffman.com/statistics/client', $chart->detailsUrl);
    }

    public function testTargetedSendsSignedUserIp(): void
    {
        $this->transport->append(MockTransport::json(['items' => [
            ['id' => 'redirect-1', 'language' => 'en', 'content' => 'Hello'],
        ]]));

        $content = $this->createClient(userIp: '203.0.113.7')->content()->targeted(2, 'en');

        $request = $this->transport->getLastRequest();
        self::assertNotNull($request);
        self::assertStringEndsWith('/content/targeted/2?language=en', $request->getUrl());
        self::assertSame('203.0.113.7', $request->getHeader('X-User-Client-IP'));
        self::assertSame(
            ServerSignature::sign('203.0.113.7', self::API_SECRET),
            $request->getHeader('X-User-Client-IP-Signature'),
        );
        self::assertTrue(ServerSignature::isRequestValid($request, self::API_SECRET));
        self::assertSame('redirect-1', $content->items[0]->id);
    }

    public function testTargetedExplicitIpOverridesResolver(): void
    {
        $this->transport->append(MockTransport::json(['items' => []]));

        $this->createClient(userIp: '203.0.113.7')->content()->targeted(1, null, '198.51.100.1');

        $request = $this->transport->getLastRequest();
        self::assertNotNull($request);
        self::assertSame('198.51.100.1', $request->getHeader('X-User-Client-IP'));
        self::assertStringEndsWith('/content/targeted/1', $request->getUrl());
    }

    public function testTargetedRequiresUserIp(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createClient()->content()->targeted(1);
    }

    public function testTargetedValidatesLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createClient(userIp: '203.0.113.7')->content()->targeted(5);
    }

    /**
     * @return list<mixed>
     */
    private static function list(mixed $value): array
    {
        self::assertIsArray($value);

        return array_values($value);
    }

    private static function item(string $id): ContentItem
    {
        return new ContentItem(
            id: $id,
            url: 'https://example.com/post-' . $id,
            language: 'uk',
            title: 'Заголовок',
            content: str_repeat('Текст ', 30),
            updatedAt: new DateTimeImmutable('2026-01-01T10:00:00+02:00'),
        );
    }
}
