<?php

declare(strict_types=1);

namespace Traffman\Sdk\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Traffman\Sdk\Exception\AccessDeniedException;
use Traffman\Sdk\Exception\ApiException;
use Traffman\Sdk\Exception\AuthenticationException;
use Traffman\Sdk\Exception\BadRequestException;
use Traffman\Sdk\Exception\ErrorCode;
use Traffman\Sdk\Exception\NotFoundException;
use Traffman\Sdk\Exception\RateLimitException;
use Traffman\Sdk\Exception\ServerException;
use Traffman\Sdk\Exception\TransportException;
use Traffman\Sdk\Exception\UnexpectedResponseException;
use Traffman\Sdk\Exception\ValidationException;
use Traffman\Sdk\Http\Response;
use Traffman\Sdk\Testing\MockTransport;
use Traffman\Sdk\Tests\Support\TestCase;

final class ErrorHandlingTest extends TestCase
{
    /**
     * @return iterable<string, array{int, class-string<ApiException>}>
     */
    public static function statusProvider(): iterable
    {
        yield 'bad request' => [400, BadRequestException::class];
        yield 'unauthorized' => [401, AuthenticationException::class];
        yield 'forbidden' => [403, AccessDeniedException::class];
        yield 'not found' => [404, NotFoundException::class];
        yield 'conflict' => [409, ApiException::class];
        yield 'server error' => [500, ServerException::class];
        yield 'bad gateway' => [502, ServerException::class];
    }

    /**
     * @param class-string<ApiException> $exceptionClass
     */
    #[DataProvider('statusProvider')]
    public function testStatusMapping(int $status, string $exceptionClass): void
    {
        $this->transport->append(MockTransport::json(['message' => 'Failure.', 'code' => 0], $status));

        try {
            $this->createClient()->client()->iam();
            self::fail('No exception was thrown.');
        } catch (ApiException $e) {
            self::assertSame($exceptionClass, $e::class);
            self::assertSame($status, $e->getStatusCode());
            self::assertSame('Failure.', $e->getMessage());
            self::assertSame(0, $e->getErrorCode());
        }
    }

    public function testValidationException(): void
    {
        $this->transport->append(MockTransport::json([
            'code' => 'request.validation.failed',
            'message' => 'Validation failed.',
            'violations' => [['path' => 'blacklistWords[0]', 'message' => 'Too short.']],
        ], 400));

        try {
            $this->createClient()->content()->pushedExternalIds();
            self::fail('No exception was thrown.');
        } catch (ValidationException $e) {
            self::assertSame(ErrorCode::VALIDATION_FAILED, $e->getErrorCode());
            self::assertSame('blacklistWords[0]', $e->getViolations()[0]->path);
            self::assertSame('Too short.', $e->getViolations()[0]->message);
        }
    }

    public function testConfirmErrorCode(): void
    {
        $this->transport->append(MockTransport::json(['message' => 'Client host already used.', 'code' => 300], 400));

        try {
            $this->createClient()->client()->confirm('https://example.com/callback');
            self::fail('No exception was thrown.');
        } catch (BadRequestException $e) {
            self::assertSame(ErrorCode::CONFIRM_HOST_ALREADY_USED, $e->getErrorCode());
        }
    }

    public function testRateLimitException(): void
    {
        $this->transport->append(
            MockTransport::json(['message' => 'Too many.', 'code' => 100], 429, ['Retry-After' => '42']),
        );

        try {
            $this->createClient()->client()->iam();
            self::fail('No exception was thrown.');
        } catch (RateLimitException $e) {
            self::assertSame(42, $e->getRetryAfter());
            self::assertSame(ErrorCode::CONFIRM_RATE_LIMITED, $e->getErrorCode());
        }
    }

    public function testErrorWithoutJsonBody(): void
    {
        $this->transport->append(new Response(503, [], '<html>Down</html>'));

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('The API responded with the HTTP status 503.');

        $this->createClient()->client()->iam();
    }

    public function testTransportExceptionIsPassedThrough(): void
    {
        $this->transport->append(new TransportException('Connection refused'));

        $this->expectException(TransportException::class);

        $this->createClient()->client()->iam();
    }

    public function testInvalidJsonOfSuccessfulResponse(): void
    {
        $this->transport->append(new Response(200, [], 'not json'));

        $this->expectException(UnexpectedResponseException::class);

        $this->createClient()->client()->iam();
    }

    public function testMissingFieldOfSuccessfulResponse(): void
    {
        $this->transport->append(MockTransport::json(['id' => 'x']));

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('"subscription"');

        $this->createClient()->client()->iam();
    }
}
