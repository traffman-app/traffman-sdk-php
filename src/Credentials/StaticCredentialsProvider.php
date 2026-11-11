<?php

declare(strict_types=1);

namespace Traffman\Sdk\Credentials;

final class StaticCredentialsProvider implements CredentialsProviderInterface
{
    public function __construct(
        private ?Credentials $credentials = null,
    ) {
    }

    public function getCredentials(): ?Credentials
    {
        return $this->credentials;
    }

    public function setCredentials(?Credentials $credentials): void
    {
        $this->credentials = $credentials;
    }
}
