<?php

declare(strict_types=1);

namespace Traffman\Sdk\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Traffman\Sdk\Config;
use Traffman\Sdk\Credentials\Credentials;
use Traffman\Sdk\Exception\InvalidArgumentException;
use Traffman\Sdk\Http\Request;
use Traffman\Sdk\UserIp\ServerUserIpResolver;

final class RequestTest extends TestCase
{
    public function testHeadersAreCaseInsensitiveAndImmutable(): void
    {
        $request = new Request('get', 'https://x', ['Content-Type' => 'a']);
        $changed = $request->withHeader('content-type', 'b');

        self::assertSame('GET', $request->getMethod());
        self::assertSame('a', $request->getHeader('CONTENT-TYPE'));
        self::assertSame(['content-type' => 'b'], $changed->getHeaders());
        self::assertFalse($changed->withoutHeader('Content-Type')->hasHeader('content-type'));
        self::assertSame('v', $request->withAttribute('k', 'v')->getAttribute('k'));
        self::assertNull($request->getAttribute('k'));
    }

    public function testConfigBuildsUrls(): void
    {
        $config = new Config('http://localhost:8080/', 'v2');

        self::assertSame('http://localhost:8080/v2/clients/iam', $config->buildUrl('/clients/iam'));
        self::assertStringStartsWith('traffman/sdk/', $config->getUserAgent());

        $this->expectException(InvalidArgumentException::class);
        new Config('ftp://example.com');
    }

    public function testCredentialsValidationAndDebugInfo(): void
    {
        $credentials = new Credentials('Key1', 'secret');

        self::assertStringNotContainsString('secret', print_r($credentials, true));
        self::assertSame('0195a7c2-1f2e-7a3b-9c4d-5e6f7a8b9c0d', $credentials
            ->withClientId('0195a7c2-1f2e-7a3b-9c4d-5e6f7a8b9c0d')
            ->requireClientId());

        $this->expectException(InvalidArgumentException::class);
        new Credentials('bad key', 'secret');
    }

    public function testServerUserIpResolver(): void
    {
        $server = [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7, 10.0.0.1',
            'HTTP_CF_CONNECTING_IP' => 'not-an-ip',
        ];

        self::assertSame('10.0.0.1', (new ServerUserIpResolver(server: $server))->resolve());
        self::assertSame('203.0.113.7', ServerUserIpResolver::withProxyHeaders($server)->resolve());
        self::assertNull((new ServerUserIpResolver(server: []))->resolve());
    }
}
