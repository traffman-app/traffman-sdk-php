<?php

declare(strict_types=1);

namespace Traffman\Sdk\Environment;

/**
 * Describes the environment of the integration (CMS, plugin, PHP versions, ...).
 *
 * The description is sent with the signed write requests (in the `_env` field) and helps the Traffman support
 * to diagnose problems of a site. Return null to send nothing.
 */
interface EnvironmentProviderInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function getEnvironment(): ?array;
}
