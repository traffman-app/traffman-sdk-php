<?php

declare(strict_types=1);

namespace Traffman\Sdk\Exception;

/**
 * HTTP 403 – the client has no access to the resource (e.g. a wrong client ID).
 */
class AccessDeniedException extends ApiException
{
}
