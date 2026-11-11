<?php

declare(strict_types=1);

namespace Traffman\Sdk\Environment;

/**
 * Sends no environment description.
 */
final class NullEnvironmentProvider implements EnvironmentProviderInterface
{
    public function getEnvironment(): ?array
    {
        return null;
    }
}
