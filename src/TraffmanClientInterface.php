<?php

declare(strict_types=1);

namespace Traffman\Sdk;

use Traffman\Sdk\Api\ApiClientInterface;
use Traffman\Sdk\ContentBlock\ContentBlockHelperInterface;
use Traffman\Sdk\Resource\ClientResourceInterface;
use Traffman\Sdk\Resource\ContentResourceInterface;
use Traffman\Sdk\Resource\HealthResourceInterface;

/**
 * Entry point of the SDK. Depend on this interface to replace the client with a stub in tests.
 */
interface TraffmanClientInterface
{
    public function client(): ClientResourceInterface;

    public function content(): ContentResourceInterface;

    public function health(): HealthResourceInterface;

    public function contentBlocks(): ContentBlockHelperInterface;

    /**
     * Low-level access for the endpoints not covered by the resources.
     */
    public function api(): ApiClientInterface;
}
