<?php

declare(strict_types=1);

namespace Traffman\Sdk;

use Closure;
use Traffman\Sdk\Api\ApiClient;
use Traffman\Sdk\Api\ApiClientInterface;
use Traffman\Sdk\Clock\ClockInterface;
use Traffman\Sdk\Clock\SystemClock;
use Traffman\Sdk\ContentBlock\ContentBlockHelper;
use Traffman\Sdk\ContentBlock\ContentBlockHelperInterface;
use Traffman\Sdk\Credentials\Credentials;
use Traffman\Sdk\Credentials\CredentialsProviderInterface;
use Traffman\Sdk\Credentials\StaticCredentialsProvider;
use Traffman\Sdk\Environment\DefaultEnvironmentProvider;
use Traffman\Sdk\Environment\EnvironmentProviderInterface;
use Traffman\Sdk\Middleware\MiddlewareInterface;
use Traffman\Sdk\Resource\ClientResource;
use Traffman\Sdk\Resource\ClientResourceInterface;
use Traffman\Sdk\Resource\ContentResource;
use Traffman\Sdk\Resource\ContentResourceInterface;
use Traffman\Sdk\Resource\HealthResource;
use Traffman\Sdk\Resource\HealthResourceInterface;
use Traffman\Sdk\Transport\TransportDiscovery;
use Traffman\Sdk\Transport\TransportInterface;
use Traffman\Sdk\UserIp\ServerUserIpResolver;
use Traffman\Sdk\UserIp\UserIpResolverInterface;
use Traffman\Sdk\Webhook\ClientConfirmedListenerInterface;
use Traffman\Sdk\Webhook\ClientConfirmWebhookHandler;
use Traffman\Sdk\Webhook\HealthConnectionWebhookHandler;

/**
 * Traffman API client.
 *
 * Every dependency is an interface with a default implementation, so the client can be adapted to any platform:
 *
 * ```php
 * $traffman = new TraffmanClient(
 *     credentials: new Credentials($apiKey, $apiSecret, $clientId),
 *     transport: new Psr18Transport($psr18Client, $requestFactory, $streamFactory), // CurlTransport by default
 * );
 *
 * $iam = $traffman->client()->iam();
 * ```
 */
final class TraffmanClient implements TraffmanClientInterface
{
    private readonly ApiClientInterface $api;
    private readonly CredentialsProviderInterface $credentialsProvider;
    private readonly ClientResourceInterface $client;
    private readonly ContentResourceInterface $content;
    private readonly HealthResourceInterface $health;
    private readonly ContentBlockHelperInterface $contentBlocks;

    /**
     * @param Credentials|CredentialsProviderInterface|null $credentials API credentials, or their provider
     *     (to read them lazily from the platform storage). Null for the anonymous calls only.
     * @param TransportInterface|null $transport HTTP transport (a discovered PSR-18 client or cURL when null).
     * @param Config|null $config Base URL, API version, default headers.
     * @param MiddlewareInterface[] $middleware Middleware run in the order given, before the request is signed.
     * @param UserIpResolverInterface|null $userIpResolver Visitor IP (`$_SERVER['REMOTE_ADDR']` when null).
     * @param EnvironmentProviderInterface|null $environmentProvider Environment description sent with
     *     the write requests ({@see DefaultEnvironmentProvider} when null).
     * @param ClockInterface|null $clock Time source of the signatures.
     */
    public function __construct(
        Credentials|CredentialsProviderInterface|null $credentials = null,
        ?TransportInterface $transport = null,
        ?Config $config = null,
        array $middleware = [],
        ?UserIpResolverInterface $userIpResolver = null,
        ?EnvironmentProviderInterface $environmentProvider = null,
        ?ClockInterface $clock = null,
    ) {
        $this->credentialsProvider = $credentials instanceof CredentialsProviderInterface
            ? $credentials
            : new StaticCredentialsProvider($credentials);

        $config ??= new Config();
        $clock ??= new SystemClock();
        $userIpResolver ??= new ServerUserIpResolver();

        $this->api = new ApiClient(
            $transport ?? TransportDiscovery::find(),
            $this->credentialsProvider,
            $config,
            $environmentProvider ?? new DefaultEnvironmentProvider(),
            $userIpResolver,
            $clock,
            $middleware,
        );

        $this->client = new ClientResource($this->api);
        $this->content = new ContentResource($this->api, $userIpResolver);
        $this->health = new HealthResource($this->api);
        $this->contentBlocks = new ContentBlockHelper($config, $this->credentialsProvider, $clock);
    }

    public function client(): ClientResourceInterface
    {
        return $this->client;
    }

    public function content(): ContentResourceInterface
    {
        return $this->content;
    }

    public function health(): HealthResourceInterface
    {
        return $this->health;
    }

    public function contentBlocks(): ContentBlockHelperInterface
    {
        return $this->contentBlocks;
    }

    public function api(): ApiClientInterface
    {
        return $this->api;
    }

    /**
     * Handler of the confirmation webhook, sharing the credentials of this client.
     *
     * @param string $callbackUrl The same URL as passed to {@see ClientResourceInterface::confirm()}.
     * @param ClientConfirmedListenerInterface|(Closure(string): void) $listener Stores the received client ID.
     */
    public function createClientConfirmWebhookHandler(
        string $callbackUrl,
        ClientConfirmedListenerInterface|Closure $listener,
    ): ClientConfirmWebhookHandler {
        return new ClientConfirmWebhookHandler($this->credentialsProvider, $callbackUrl, $listener);
    }

    public function createHealthConnectionWebhookHandler(): HealthConnectionWebhookHandler
    {
        return new HealthConnectionWebhookHandler();
    }
}
