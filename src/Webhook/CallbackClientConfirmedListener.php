<?php

declare(strict_types=1);

namespace Traffman\Sdk\Webhook;

use Closure;

final class CallbackClientConfirmedListener implements ClientConfirmedListenerInterface
{
    /**
     * @param Closure(string): void $callback
     */
    public function __construct(
        private readonly Closure $callback,
    ) {
    }

    public function onClientConfirmed(string $clientId): void
    {
        ($this->callback)($clientId);
    }
}
