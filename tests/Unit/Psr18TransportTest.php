<?php

declare(strict_types=1);

namespace Traffman\Sdk\Tests\Unit;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response as PsrResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Traffman\Sdk\Exception\TransportException;
use Traffman\Sdk\Http\Request;
use Traffman\Sdk\Transport\Psr18Transport;

final class Psr18TransportTest extends TestCase
{
    public function testConvertsRequestAndResponse(): void
    {
        $client = new class implements ClientInterface {
            public ?RequestInterface $request = null;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->request = $request;

                return new PsrResponse(201, ['X-Test' => ['a', 'b']], '{"ok":true}');
            }
        };

        $factory = new Psr17Factory();
        $transport = new Psr18Transport($client, $factory, $factory);

        $response = $transport->send(
            new Request('put', 'https://api.test/v1/x?y=1', ['Accept' => 'application/json'], '{"a":1}'),
        );

        self::assertNotNull($client->request);
        self::assertSame('PUT', $client->request->getMethod());
        self::assertSame('https://api.test/v1/x?y=1', (string) $client->request->getUri());
        self::assertSame('application/json', $client->request->getHeaderLine('Accept'));
        self::assertSame('{"a":1}', (string) $client->request->getBody());

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('a, b', $response->getHeader('x-test'));
        self::assertSame(['ok' => true], $response->toArray());
    }

    public function testWrapsClientExceptions(): void
    {
        $client = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                throw new class ('Connection refused', $request) extends RuntimeException implements
                    NetworkExceptionInterface
                {
                    public function __construct(string $message, private readonly RequestInterface $request)
                    {
                        parent::__construct($message);
                    }

                    public function getRequest(): RequestInterface
                    {
                        return $this->request;
                    }
                };
            }
        };

        $factory = new Psr17Factory();

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Connection refused');

        (new Psr18Transport($client, $factory, $factory))->send(new Request('GET', 'https://api.test/'));
    }
}
