<?php

declare(strict_types=1);

namespace Traffman\Sdk\Transport;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use LogicException;
use Throwable;

/**
 * Finds a transport when none is passed explicitly:
 * 1. a PSR-18 client found by `php-http/discovery` (when the package is installed);
 * 2. {@see CurlTransport} (when ext-curl is loaded).
 */
final class TransportDiscovery
{
    public static function find(): TransportInterface
    {
        if (class_exists(Psr18ClientDiscovery::class) && class_exists(Psr17FactoryDiscovery::class)) {
            try {
                return new Psr18Transport(
                    Psr18ClientDiscovery::find(),
                    Psr17FactoryDiscovery::findRequestFactory(),
                    Psr17FactoryDiscovery::findStreamFactory(),
                );
            } catch (Throwable) {
                // no implementation is installed, the next option is tried
            }
        }

        if (extension_loaded('curl')) {
            return new CurlTransport();
        }

        throw new LogicException(
            'No HTTP transport is available: install a PSR-18 client with "php-http/discovery", '
            . 'enable the "curl" PHP extension, or pass your own TransportInterface implementation.',
        );
    }

    private function __construct()
    {
    }
}
