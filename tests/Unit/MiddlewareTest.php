<?php

declare(strict_types=1);

namespace Traffman\Sdk\Tests\Unit;

use Traffman\Sdk\Cache\InMemoryCache;
use Traffman\Sdk\Credentials\Credentials;
use Traffman\Sdk\Exception\RateLimitException;
use Traffman\Sdk\Exception\ServerException;
use Traffman\Sdk\Exception\TransportException;
use Traffman\Sdk\Http\Operation;
use Traffman\Sdk\Http\Request;
use Traffman\Sdk\Http\RequestAttribute;
use Traffman\Sdk\Http\RequestHandlerInterface;
use Traffman\Sdk\Http\Response;
use Traffman\Sdk\Middleware\CacheMiddleware;
use Traffman\Sdk\Middleware\CallableMiddleware;
use Traffman\Sdk\Middleware\HeadersMiddleware;
use Traffman\Sdk\Middleware\RetryMiddleware;
use Traffman\Sdk\Testing\MockTransport;
use Traffman\Sdk\Tests\Support\Fixtures;
use Traffman\Sdk\Tests\Support\ServerSignature;
use Traffman\Sdk\Tests\Support\TestCase;

final class MiddlewareTest extends TestCase
{
    /**
     * @var list<float>
     */
    private array $sleeps = [];

    public function testMiddlewareRunsInOrderAndBeforeSigning(): void
    {
        $this->transport->append(MockTransport::json(Fixtures::iam()));
        $log = [];

        $client = $this->createClient(middleware: [
            new CallableMiddleware(static function (Request $request, RequestHandlerInterface $next) use (&$log) {
                $log[] = 'first:' . self::stringAttribute($request, RequestAttribute::OPERATION);
                self::assertNull($request->getHeader('Authorization'));

                return $next->handle($request->withUrl($request->getUrl() . '?extra=1'));
            }),
            new CallableMiddleware(static function (Request $request, RequestHandlerInterface $next) use (&$log) {
                $log[] = 'second';

                return $next->handle($request);
            }),
        ]);

        $client->client()->iam();

        self::assertSame(['first:' . Operation::CLIENT_IAM, 'second'], $log);

        $request = $this->transport->getLastRequest();
        self::assertNotNull($request);
        self::assertStringEndsWith('?extra=1', $request->getUrl());
        // the change made by the middleware is covered by the signature
        self::assertTrue(ServerSignature::isRequestValid($request, self::API_SECRET));
    }

    public function testHeadersMiddleware(): void
    {
        $this->transport->append(MockTransport::json(Fixtures::chart()));

        $this->createClient(middleware: [new HeadersMiddleware(['X-Timezone' => 'UTC', 'Host' => 'api.local'])])
            ->content()
            ->statistics(timezone: 'Europe/Kyiv');

        $request = $this->transport->getLastRequest();
        self::assertNotNull($request);
        self::assertSame('Europe/Kyiv', $request->getHeader('x-timezone'));
        self::assertSame('api.local', $request->getHeader('Host'));
    }

    public function testRetryAfterRateLimitAndTransportError(): void
    {
        $this->transport
            ->append(MockTransport::json(['message' => 'Too many.'], 429, ['Retry-After' => '1']))
            ->append(new TransportException('Timeout'))
            ->append(MockTransport::json(Fixtures::iam()));

        $iam = $this->createClient(middleware: [$this->createRetryMiddleware()])->client()->iam();

        self::assertSame(self::CLIENT_ID, $iam->id);
        // the server delay first, then the exponential backoff of the second retry
        self::assertSame([1.0, 2.0], $this->sleeps);
        self::assertSame(0, $this->transport->countPending());
    }

    public function testRetryGivesUpWhenServerAsksToWaitTooLong(): void
    {
        $this->transport->append(MockTransport::json(['message' => 'Too many.'], 429, ['Retry-After' => '60']));

        try {
            $this->createClient(middleware: [$this->createRetryMiddleware()])->client()->iam();
            self::fail('No exception was thrown.');
        } catch (RateLimitException $e) {
            self::assertSame(60, $e->getRetryAfter());
            self::assertSame([], $this->sleeps);
        }
    }

    public function testRetryDoesNotRepeatPost(): void
    {
        $this->transport->append(new TransportException('Timeout'));

        $this->expectException(TransportException::class);

        try {
            $this->createClient(middleware: [$this->createRetryMiddleware()])
                ->client()
                ->confirm('https://example.com/callback');
        } finally {
            self::assertCount(1, $this->transport->getRequests());
        }
    }

    public function testRetryStopsAfterMaxRetries(): void
    {
        $this->transport
            ->append(new Response(503))
            ->append(new Response(503))
            ->append(new Response(503));

        try {
            $this->createClient(middleware: [$this->createRetryMiddleware()])->client()->iam();
            self::fail('No exception was thrown.');
        } catch (ServerException) {
            self::assertCount(3, $this->transport->getRequests());
            self::assertSame([1.0, 2.0], $this->sleeps);
        }
    }

    public function testCacheMiddlewareCachesReadOperationsPerApiKey(): void
    {
        $cache = new InMemoryCache();
        $this->transport
            ->append(MockTransport::json(Fixtures::iam()))
            ->append(MockTransport::json(Fixtures::iam()));

        $client = $this->createClient(middleware: [new CacheMiddleware($cache)]);
        $client->client()->iam();
        $client->client()->iam();

        self::assertCount(1, $this->transport->getRequests());

        // other credentials never get the cached response of the first ones
        $other = $this->createClient(new Credentials('OtherKey', 'other'), [new CacheMiddleware($cache)]);
        $other->client()->iam();

        self::assertCount(2, $this->transport->getRequests());
    }

    public function testCacheMiddlewareSkipsTargetedContentAndErrors(): void
    {
        $cache = new InMemoryCache();
        $this->transport
            ->append(MockTransport::json(['items' => []]))
            ->append(MockTransport::json(['items' => []]))
            ->append(MockTransport::json(['message' => 'Down'], 500))
            ->append(MockTransport::json(Fixtures::dashboard()));

        $client = $this->createClient(middleware: [new CacheMiddleware($cache)], userIp: '203.0.113.7');
        $client->content()->targeted(1);
        $client->content()->targeted(1);

        try {
            $client->client()->dashboard();
        } catch (ServerException) {
            // the error is not cached, so the next call is sent again
        }

        $client->client()->dashboard();

        self::assertCount(4, $this->transport->getRequests());
    }

    private static function stringAttribute(Request $request, string $name): string
    {
        $value = $request->getAttribute($name);
        self::assertIsString($value);

        return $value;
    }

    private function createRetryMiddleware(): RetryMiddleware
    {
        return new RetryMiddleware(
            maxRetries: 2,
            delay: 1.0,
            maxDelay: 5.0,
            sleeper: function (float $seconds): void {
                $this->sleeps[] = $seconds;
            },
        );
    }
}
