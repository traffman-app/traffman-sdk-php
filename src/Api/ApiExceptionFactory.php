<?php

declare(strict_types=1);

namespace Traffman\Sdk\Api;

use Traffman\Sdk\Exception\AccessDeniedException;
use Traffman\Sdk\Exception\ApiException;
use Traffman\Sdk\Exception\AuthenticationException;
use Traffman\Sdk\Exception\BadRequestException;
use Traffman\Sdk\Exception\ErrorCode;
use Traffman\Sdk\Exception\NotFoundException;
use Traffman\Sdk\Exception\RateLimitException;
use Traffman\Sdk\Exception\ServerException;
use Traffman\Sdk\Exception\UnexpectedResponseException;
use Traffman\Sdk\Exception\ValidationException;
use Traffman\Sdk\Http\Response;
use Traffman\Sdk\Model\Violation;

/**
 * Creates the exception of an error response (4xx/5xx).
 */
final class ApiExceptionFactory
{
    public static function create(Response $response): ApiException
    {
        try {
            $payload = $response->toArray();
        } catch (UnexpectedResponseException) {
            $payload = [];
        }

        $status = $response->getStatusCode();
        $code = $payload['code'] ?? null;
        $code = is_int($code) || is_string($code) ? $code : null;

        $message = $payload['message'] ?? null;
        $message = is_string($message) && $message !== ''
            ? $message
            : sprintf('The API responded with the HTTP status %d.', $status);

        return match (true) {
            $status === 400 && $code === ErrorCode::VALIDATION_FAILED => new ValidationException(
                $message,
                $response,
                $code,
                $payload,
                self::createViolations($payload['violations'] ?? null),
            ),
            $status === 400 => new BadRequestException($message, $response, $code, $payload),
            $status === 401 => new AuthenticationException($message, $response, $code, $payload),
            $status === 403 => new AccessDeniedException($message, $response, $code, $payload),
            $status === 404 => new NotFoundException($message, $response, $code, $payload),
            $status === 429 => new RateLimitException(
                $message,
                $response,
                $code,
                $payload,
                self::parseRetryAfter($response->getHeader('Retry-After')),
            ),
            $status >= 500 => new ServerException($message, $response, $code, $payload),
            default => new ApiException($message, $response, $code, $payload),
        };
    }

    /**
     * @return Violation[]
     */
    private static function createViolations(mixed $violations): array
    {
        if (!is_array($violations)) {
            return [];
        }

        $result = [];

        foreach ($violations as $violation) {
            if (
                is_array($violation)
                && is_string($violation['path'] ?? null)
                && is_string($violation['message'] ?? null)
            ) {
                $result[] = new Violation($violation['path'], $violation['message'], $violation);
            }
        }

        return $result;
    }

    private static function parseRetryAfter(?string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return (int) $value;
        }

        $timestamp = strtotime($value);

        return $timestamp !== false ? max(0, $timestamp - time()) : null;
    }

    private function __construct()
    {
    }
}
