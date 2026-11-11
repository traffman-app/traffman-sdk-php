<?php

declare(strict_types=1);

namespace Traffman\Sdk\Environment;

use Traffman\Sdk\Version;

/**
 * Describes the SDK and PHP, extended with the integration's own data
 * (e.g. `['cms' => ['name' => 'WordPress', 'version' => '6.8']]`).
 */
final class DefaultEnvironmentProvider implements EnvironmentProviderInterface
{
    /**
     * @param array<string, mixed> $extra Data merged over the default one.
     */
    public function __construct(
        private readonly array $extra = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getEnvironment(): array
    {
        /** @var array<string, mixed> */
        return array_replace_recursive(
            [
                'sdk' => [
                    'name' => Version::NAME,
                    'version' => Version::VERSION,
                ],
                'php' => [
                    'version' => PHP_VERSION,
                    'sapi' => PHP_SAPI,
                ],
            ],
            $this->extra,
        );
    }
}
