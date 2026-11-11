<?php

declare(strict_types=1);

namespace Traffman\Sdk\Credentials;

/**
 * Provides the current API credentials on every request.
 *
 * Implement it to read the credentials lazily from the platform storage (WordPress options, Drupal config,
 * a database, environment variables), so a client confirmed in the meantime is picked up without
 * rebuilding the SDK client.
 */
interface CredentialsProviderInterface
{
    /**
     * @return Credentials|null Null when the site is not connected yet (only the anonymous calls work then).
     */
    public function getCredentials(): ?Credentials;
}
