<?php

declare(strict_types=1);

namespace Traffman\Sdk\Exception;

use RuntimeException;
use Throwable;

/**
 * The response was received, but its body does not have the expected structure.
 */
class UnexpectedResponseException extends RuntimeException implements TraffmanExceptionInterface
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
