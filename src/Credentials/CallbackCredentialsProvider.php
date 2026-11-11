<?php

declare(strict_types=1);

namespace Traffman\Sdk\Credentials;

use Closure;

/**
 * Credentials from a closure, e.g. `fn () => new Credentials(get_option('key'), get_option('secret'))`.
 */
final class CallbackCredentialsProvider implements CredentialsProviderInterface
{
    /**
     * @param Closure(): ?Credentials $callback
     */
    public function __construct(
        private readonly Closure $callback,
    ) {
    }

    public function getCredentials(): ?Credentials
    {
        return ($this->callback)();
    }
}
