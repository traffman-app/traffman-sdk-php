<?php

declare(strict_types=1);

namespace Traffman\Sdk\Enum;

/**
 * Integration types known to the API (to get the required/preferable integration version).
 */
enum ClientType: string
{
    case WORDPRESS = 'wordpress';
    case DRUPAL = 'drupal';
}
