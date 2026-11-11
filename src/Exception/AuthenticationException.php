<?php

declare(strict_types=1);

namespace Traffman\Sdk\Exception;

/**
 * HTTP 401 – the API credentials are invalid, the request signature is wrong or expired,
 * or the client is not confirmed / not active.
 */
class AuthenticationException extends ApiException
{
}
