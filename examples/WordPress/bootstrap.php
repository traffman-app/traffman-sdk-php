<?php

/**
 * Wiring of the SDK in a WordPress plugin.
 *
 * phpcs:disable PSR1.Files.SideEffects
 */

declare(strict_types=1);

use Example\WordPress\WpHttpTransport;
use Example\WordPress\WpOptionsCredentialsProvider;
use Example\WordPress\WpTransientCache;
use Traffman\Sdk\Environment\DefaultEnvironmentProvider;
use Traffman\Sdk\Middleware\CacheMiddleware;
use Traffman\Sdk\Middleware\RetryMiddleware;
use Traffman\Sdk\TraffmanClient;
use Traffman\Sdk\UserIp\ServerUserIpResolver;
use Traffman\Sdk\Webhook\WebhookResponse;

function traffman(): TraffmanClient
{
    static $client = null;

    return $client ??= new TraffmanClient(
        credentials: new WpOptionsCredentialsProvider(),
        transport: new WpHttpTransport(timeout: 10),
        middleware: [
            new CacheMiddleware(new WpTransientCache(), ttl: 300),
            new RetryMiddleware(maxRetries: 1),
        ],
        // most WordPress sites are behind a CDN or a reverse proxy
        userIpResolver: ServerUserIpResolver::withProxyHeaders(),
        environmentProvider: new DefaultEnvironmentProvider([
            'cms' => ['name' => 'WordPress', 'version' => get_bloginfo('version'), 'multisite' => is_multisite()],
        ]),
    );
}

function traffman_rest_response(WebhookResponse $response): WP_REST_Response
{
    return new WP_REST_Response($response->body, $response->statusCode);
}

add_action('rest_api_init', static function (): void {
    register_rest_route('traffman', '/client/confirm', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => static fn(WP_REST_Request $request): WP_REST_Response => traffman_rest_response(
            traffman()
                ->createClientConfirmWebhookHandler(
                    rest_url('traffman/client/confirm'),
                    new WpOptionsCredentialsProvider(),
                )
                ->handleRaw($request->get_body()),
        ),
    ]);

    register_rest_route('traffman', '/client/health/connection', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => static fn(WP_REST_Request $request): WP_REST_Response => traffman_rest_response(
            traffman()->createHealthConnectionWebhookHandler()->handleRaw($request->get_body()),
        ),
    ]);
});

// e.g. on saving the API credentials:
// traffman()->client()->confirm(rest_url('traffman/client/confirm'));
//
// e.g. on the dashboard page:
// $iam = traffman()->client()->iam(\Traffman\Sdk\Enum\ClientType::WORDPRESS);
