<?php

declare(strict_types=1);

namespace Traffman\Sdk\Tests\Support;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Traffman\Sdk\Clock\FrozenClock;
use Traffman\Sdk\Credentials\Credentials;
use Traffman\Sdk\Environment\NullEnvironmentProvider;
use Traffman\Sdk\Environment\EnvironmentProviderInterface;
use Traffman\Sdk\Middleware\MiddlewareInterface;
use Traffman\Sdk\Testing\MockTransport;
use Traffman\Sdk\TraffmanClient;
use Traffman\Sdk\UserIp\FixedUserIpResolver;

abstract class TestCase extends BaseTestCase
{
    protected const API_KEY = 'AbC123key';
    protected const API_SECRET = 'top-secret';
    protected const CLIENT_ID = '0195a7c2-1f2e-7a3b-9c4d-5e6f7a8b9c0d';
    protected const TIMESTAMP = 1_767_225_600;

    protected MockTransport $transport;

    protected function setUp(): void
    {
        $this->transport = new MockTransport();
    }

    protected static function credentials(?string $clientId = self::CLIENT_ID): Credentials
    {
        return new Credentials(self::API_KEY, self::API_SECRET, $clientId);
    }

    /**
     * @param MiddlewareInterface[] $middleware
     */
    protected function createClient(
        ?Credentials $credentials = null,
        array $middleware = [],
        ?string $userIp = null,
        ?EnvironmentProviderInterface $environmentProvider = null,
    ): TraffmanClient {
        return new TraffmanClient(
            credentials: $credentials ?? self::credentials(),
            transport: $this->transport,
            middleware: $middleware,
            userIpResolver: new FixedUserIpResolver($userIp),
            environmentProvider: $environmentProvider ?? new NullEnvironmentProvider(),
            clock: FrozenClock::fromTimestamp(self::TIMESTAMP),
        );
    }

    /**
     * @return array<mixed>
     */
    protected static function decodeBody(string $body): array
    {
        $data = json_decode($body, true);
        self::assertIsArray($data);

        return $data;
    }
}
