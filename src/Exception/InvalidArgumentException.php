<?php

declare(strict_types=1);

namespace Traffman\Sdk\Exception;

/**
 * An argument passed to the SDK is invalid (detected before any request is sent).
 */
class InvalidArgumentException extends \InvalidArgumentException implements TraffmanExceptionInterface
{
}
