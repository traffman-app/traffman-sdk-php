<?php

declare(strict_types=1);

namespace Traffman\Sdk\Exception;

/**
 * Known values of {@see ApiException::getErrorCode()}.
 */
final class ErrorCode
{
    /**
     * HTTP 400 – the request data did not pass the validation ({@see ValidationException}).
     */
    public const VALIDATION_FAILED = 'request.validation.failed';

    /**
     * HTTP 400 of the client confirmation – no client exists with the API key.
     */
    public const CONFIRM_INVALID_CREDENTIALS = 100;

    /**
     * HTTP 400 of the client confirmation – the client is confirmed for another host than the callback URL host.
     */
    public const CONFIRM_HOST_MISMATCH = 200;

    /**
     * HTTP 400 of the client confirmation – another client is already confirmed for the callback URL host.
     */
    public const CONFIRM_HOST_ALREADY_USED = 300;

    /**
     * HTTP 429 of the client confirmation – the confirmation was requested too recently.
     */
    public const CONFIRM_RATE_LIMITED = 100;

    private function __construct()
    {
    }
}
