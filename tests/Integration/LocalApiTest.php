<?php

declare(strict_types=1);

namespace Traffman\Sdk\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Traffman\Sdk\Config;
use Traffman\Sdk\Credentials\Credentials;
use Traffman\Sdk\Enum\ClientType;
use Traffman\Sdk\Enum\StatisticType;
use Traffman\Sdk\Exception\AuthenticationException;
use Traffman\Sdk\Transport\CurlTransport;
use Traffman\Sdk\TraffmanClient;

/**
 * Read-only calls against a real API (nothing is written).
 *
 * Runs only when `TRAFFMAN_API_URL` is set; the authenticated calls also need `TRAFFMAN_API_KEY`,
 * `TRAFFMAN_API_SECRET` and `TRAFFMAN_CLIENT_ID` of a confirmed client.
 */
final class LocalApiTest extends TestCase
{
    public function testHealthCommon(): void
    {
        $status = $this->createClient(null)->health()->common();

        self::assertSame('OK', $status->status);
    }

    public function testAuthenticatedReadCalls(): void
    {
        $key = getenv('TRAFFMAN_API_KEY');
        $secret = getenv('TRAFFMAN_API_SECRET');
        $clientId = getenv('TRAFFMAN_CLIENT_ID');

        if (!is_string($key) || !is_string($secret) || !is_string($clientId) || $key === '') {
            self::markTestSkipped('The API credentials are not set.');
        }

        $client = $this->createClient(new Credentials($key, $secret, $clientId));

        self::assertSame($clientId, $client->client()->iam(ClientType::WORDPRESS)->id);
        self::assertGreaterThanOrEqual(0, $client->client()->dashboard()->content->total);
        self::assertNotSame('', $client->content()->statistics(StatisticType::CLIENT)->period);
        self::assertIsList($client->content()->pushedExternalIds());
    }

    public function testWrongCredentialsAreRejected(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->createClient(new Credentials('nonexistentkey', 'secret', '00000000-0000-0000-0000-000000000000'))
            ->client()
            ->iam();
    }

    private function createClient(?Credentials $credentials): TraffmanClient
    {
        $url = getenv('TRAFFMAN_API_URL');

        if (!is_string($url) || $url === '') {
            self::markTestSkipped('TRAFFMAN_API_URL is not set.');
        }

        return new TraffmanClient(
            credentials: $credentials,
            transport: new CurlTransport(timeout: 15),
            config: new Config($url),
        );
    }
}
