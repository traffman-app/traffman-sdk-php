<?php

declare(strict_types=1);

namespace Traffman\Sdk\Tests\Unit;

use Traffman\Sdk\Credentials\StaticCredentialsProvider;
use Traffman\Sdk\Tests\Support\ServerSignature;
use Traffman\Sdk\Tests\Support\TestCase;
use Traffman\Sdk\Webhook\ClientConfirmWebhookHandler;
use Traffman\Sdk\Webhook\HealthConnectionWebhookHandler;

final class WebhookTest extends TestCase
{
    private const CALLBACK_URL = 'https://example.com/wp-json/traffman/client/confirm';

    private ?string $confirmedClientId = null;

    public function testValidationCallIsAnsweredWithTokenSignature(): void
    {
        $response = $this->createHandler()->handle([
            'type' => 'validation',
            'signature' => $this->requestSignature(),
            'token' => 'random-token',
        ]);

        self::assertSame(200, $response->statusCode);
        self::assertSame(['signature' => ServerSignature::sign('random-token', self::API_SECRET)], $response->body);
        self::assertSame(['Content-Type' => 'application/json'], $response->getHeaders());
        self::assertNull($this->confirmedClientId);
    }

    public function testSuccessCallStoresClientId(): void
    {
        $response = $this->createHandler()->handleRaw((string) json_encode([
            'type' => 'success',
            'signature' => $this->requestSignature(),
            'id' => self::CLIENT_ID,
        ]));

        self::assertSame(202, $response->statusCode);
        self::assertSame('', $response->getContent());
        self::assertSame(self::CLIENT_ID, $this->confirmedClientId);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidPayloadProvider(): iterable
    {
        $signature = ServerSignature::confirmationSignature(self::API_KEY, self::CALLBACK_URL, self::API_SECRET);

        yield 'wrong signature' => [['type' => 'success', 'signature' => 'wrong', 'id' => self::CLIENT_ID]];
        yield 'signature of another type' => [['type' => 'success', 'signature' => ['x'], 'id' => self::CLIENT_ID]];
        yield 'no signature' => [['type' => 'success', 'id' => self::CLIENT_ID]];
        yield 'unknown type' => [['type' => 'other', 'signature' => $signature]];
        yield 'invalid client ID' => [['type' => 'success', 'signature' => $signature, 'id' => 'x']];
        yield 'no token' => [['type' => 'validation', 'signature' => $signature]];
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidPayloadProvider')]
    public function testInvalidCallsAreRejected(array $payload): void
    {
        $response = $this->createHandler()->handle($payload);

        self::assertSame(400, $response->statusCode);
        self::assertNull($this->confirmedClientId);
    }

    public function testInvalidJsonIsRejected(): void
    {
        self::assertSame(400, $this->createHandler()->handleRaw('nope')->statusCode);
        self::assertSame(400, $this->createHandler()->handleRaw('[1,2]')->statusCode);
    }

    public function testWithoutCredentialsEverythingIsRejected(): void
    {
        $handler = new ClientConfirmWebhookHandler(
            new StaticCredentialsProvider(),
            self::CALLBACK_URL,
            static fn(string $id) => null,
        );

        $response = $handler->handle(['type' => 'validation', 'signature' => 'x', 'token' => 't']);

        self::assertSame(400, $response->statusCode);
    }

    public function testHandlerFromClient(): void
    {
        $handler = $this->createClient(self::credentials(null))->createClientConfirmWebhookHandler(
            self::CALLBACK_URL,
            function (string $id): void {
                $this->confirmedClientId = $id;
            },
        );

        $handler->handle(['type' => 'success', 'signature' => $this->requestSignature(), 'id' => self::CLIENT_ID]);

        self::assertSame(self::CLIENT_ID, $this->confirmedClientId);
    }

    public function testHealthConnectionEchoesToken(): void
    {
        $handler = new HealthConnectionWebhookHandler();

        self::assertSame('{"token":"abc"}', $handler->handleRaw('{"token":"abc"}')->getContent());
        self::assertSame(['token' => null], $handler->handleRaw('')->body);
        self::assertSame(200, $handler->handle([])->statusCode);
    }

    private function createHandler(): ClientConfirmWebhookHandler
    {
        return new ClientConfirmWebhookHandler(
            new StaticCredentialsProvider(self::credentials(null)),
            self::CALLBACK_URL,
            function (string $id): void {
                $this->confirmedClientId = $id;
            },
        );
    }

    private function requestSignature(): string
    {
        return ServerSignature::confirmationSignature(self::API_KEY, self::CALLBACK_URL, self::API_SECRET);
    }
}
