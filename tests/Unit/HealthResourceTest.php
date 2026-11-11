<?php

declare(strict_types=1);

namespace Traffman\Sdk\Tests\Unit;

use Traffman\Sdk\Testing\MockTransport;
use Traffman\Sdk\Tests\Support\TestCase;
use Traffman\Sdk\TraffmanClient;

final class HealthResourceTest extends TestCase
{
    public function testHealthCallsWorkWithoutCredentials(): void
    {
        $this->transport
            ->append(MockTransport::json(['status' => 'OK', 'clientIp' => '127.0.0.1']))
            ->append(MockTransport::json(['status' => true, 'message' => null]))
            ->append(MockTransport::json(['status' => false, 'message' => 'HTTP status code: 404;']));

        $health = (new TraffmanClient(transport: $this->transport))->health();

        self::assertSame('OK', $health->common()->status);
        self::assertTrue($health->checkConnection('https://example.com/health')->status);

        $result = $health->checkContentUrl('https://example.com/');
        self::assertFalse($result->status);
        self::assertSame('HTTP status code: 404;', $result->message);

        $requests = $this->transport->getRequests();
        self::assertSame('https://api.traffman.com/v1/health/common', $requests[0]->getUrl());
        self::assertSame('https://api.traffman.com/v1/clients/health/connection', $requests[1]->getUrl());
        self::assertSame('{"callbackUrl":"https://example.com/health"}', $requests[1]->getBody());
        self::assertSame('{"url":"https://example.com/"}', $requests[2]->getBody());

        foreach ($requests as $request) {
            self::assertNull($request->getHeader('Authorization'));
        }
    }
}
