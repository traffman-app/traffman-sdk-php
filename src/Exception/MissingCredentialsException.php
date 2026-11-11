<?php

declare(strict_types=1);

namespace Traffman\Sdk\Exception;

use LogicException;

/**
 * The operation requires API credentials (or the client ID) which are not configured.
 */
class MissingCredentialsException extends LogicException implements TraffmanExceptionInterface
{
}
