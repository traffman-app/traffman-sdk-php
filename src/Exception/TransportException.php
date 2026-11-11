<?php

declare(strict_types=1);

namespace Traffman\Sdk\Exception;

use RuntimeException;

/**
 * The request could not be sent or no response was received (network error, timeout, DNS, TLS, ...).
 */
class TransportException extends RuntimeException implements TraffmanExceptionInterface
{
}
