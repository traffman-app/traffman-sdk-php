# Traffman PHP SDK

PHP client for the [Traffman](https://traffman.com) API, the content exchange network for sites.

- **Transport-agnostic.** The SDK sends requests through its own small `TransportInterface`. It ships a PSR-18
  adapter and a dependency-free cURL transport, and a transport for any platform takes about 20 lines
  (see [the WordPress example](examples/WordPress/WpHttpTransport.php)).
- **No runtime dependencies** beyond PHP 8.1+ and `ext-json`.
- **Every moving part is an interface:** credentials, transport, cache, clock, visitor IP, environment
  description, middleware, the resources and the client itself.
- `declare(strict_types=1)` everywhere, PHPStan level max, PSR-12.

## Installation

The package is not on Packagist yet. Install it from the GitHub repository:

```bash
composer config repositories.traffman-sdk vcs https://github.com/traffman-app/traffman-sdk-php
composer require traffman/sdk:dev-main
```

This adds the repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/traffman-app/traffman-sdk-php"
        }
    ],
    "require": {
        "traffman/sdk": "dev-main"
    }
}
```

`dev-main` follows the `main` branch; `composer.lock` pins the installed commit.

## Quick start

```php
use Traffman\Sdk\Credentials\Credentials;
use Traffman\Sdk\TraffmanClient;

$traffman = new TraffmanClient(
    credentials: new Credentials($apiKey, $apiSecret, $clientId),
);

$iam = $traffman->client()->iam();          // client info and subscription
$dashboard = $traffman->client()->dashboard();
```

When no transport is passed, the SDK uses a PSR-18 client found by `php-http/discovery` if one is installed,
and `CurlTransport` otherwise.

## Transports

| Transport | When to use |
|---|---|
| `Transport\Psr18Transport` | Any PSR-18 client: Guzzle, Symfony HttpClient, Buzz, ... |
| `Transport\CurlTransport` | No dependencies, needs `ext-curl` |
| Your own `TransportInterface` | The platform HTTP API (WordPress `wp_remote_request()`, ...) |
| `Testing\MockTransport` | Tests: returns queued responses and records the requests |

```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Traffman\Sdk\Transport\Psr18Transport;

$factory = new HttpFactory();
$transport = new Psr18Transport(new Client(['timeout' => 10, 'allow_redirects' => false]), $factory, $factory);

$traffman = new TraffmanClient(credentials: $credentials, transport: $transport);
```

A transport must return 4xx and 5xx responses as a `Response` instead of throwing. It throws
`TransportException` only when no response was received. It must not follow redirects.

```php
use Traffman\Sdk\Http\Request;
use Traffman\Sdk\Http\Response;
use Traffman\Sdk\Transport\TransportInterface;

final class MyTransport implements TransportInterface
{
    public function send(Request $request): Response
    {
        // $request->getMethod(), getUrl(), getHeaders(), getBody()
        return new Response($status, $headers, $body);
    }
}
```

## Configuration

```php
use Traffman\Sdk\Config;

new TraffmanClient(
    credentials: $credentials,                // Credentials or a CredentialsProviderInterface
    transport: $transport,                    // TransportInterface
    config: new Config(
        baseUrl: 'https://api.traffman.com',  // e.g. a staging or a local API
        apiVersion: 'v1',
        userAgent: 'my-plugin/1.0',
        headers: ['X-Timezone' => 'Europe/Kyiv'],
    ),
    middleware: [/* MiddlewareInterface */],
    userIpResolver: $resolver,                // UserIpResolverInterface
    environmentProvider: $environment,        // EnvironmentProviderInterface
    clock: $clock,                            // ClockInterface (PSR-20 compatible)
);
```

### Credentials

The API key and secret come from the Traffman account. The client ID arrives later, from the confirmation
webhook (see below), so it is optional until then. A `CredentialsProviderInterface` is called on every request.
It can read the credentials lazily from the platform storage, so a client confirmed in the meantime is picked up
without rebuilding the SDK client:

```php
use Traffman\Sdk\Credentials\CallbackCredentialsProvider;

$credentials = new CallbackCredentialsProvider(
    fn () => new Credentials($store->get('key'), $store->get('secret'), $store->get('client_id')),
);
```

### Visitor IP

The targeted content needs the IP of the site visitor. The SDK sends it, signed, in `X-User-Client-IP`.
The default `ServerUserIpResolver` trusts only `REMOTE_ADDR`. Behind a CDN or a reverse proxy, enable the
proxy headers explicitly. Clients can forge these headers when the request does not pass through the proxy.

```php
use Traffman\Sdk\UserIp\ServerUserIpResolver;

new TraffmanClient(credentials: $credentials, userIpResolver: ServerUserIpResolver::withProxyHeaders());
```

## API

### Client

```php
use Traffman\Sdk\Enum\ClientType;
use Traffman\Sdk\Enum\ContentLanguageMode;
use Traffman\Sdk\Model\SettingsUpdate;

$traffman->client()->confirm('https://example.com/traffman/confirm'); // starts the confirmation
$traffman->client()->iam(ClientType::WORDPRESS);                      // + required/preferable plugin version
$traffman->client()->dashboard();

// null fields are left unchanged, an empty list clears a setting
$traffman->client()->updateSettings(new SettingsUpdate(
    contentLanguage: ContentLanguageMode::EXACT,
    contentTopicTags: ['travel', 'food'],
    blacklistDomains: ['example.org'],
    blacklistWords: ['casino'],
));
```

### Content

```php
use Traffman\Sdk\Content\ContentNormalizer;
use Traffman\Sdk\Enum\StatisticPeriod;
use Traffman\Sdk\Enum\StatisticType;
use Traffman\Sdk\Model\ContentItem;

$normalizer = new ContentNormalizer(); // HTML → plain text of the allowed length (needs ext-mbstring)

$item = new ContentItem(
    id: (string) $post->id,
    url: $post->url,
    language: 'uk',
    title: $normalizer->normalizeTitle($post->title),
    content: $normalizer->normalize($post->html),
    updatedAt: $post->updatedAt,
);

// $mapIds is the FULL list of the IDs that must exist: the API removes every item missing from it
$result = $traffman->content()->push($allSelectedIds, [$item]);                 // up to 100 items
$results = $traffman->content()->pushInBatches($allSelectedIds, $itemsIterable); // any number of items

foreach ($result->failed as $failure) {
    // $failure->id, $failure->violations[]->path / ->message
}

$traffman->content()->pushedExternalIds();
$traffman->content()->statistics(StatisticType::CONTENT, StatisticPeriod::MONTH, 'Europe/Kyiv');
$traffman->content()->targeted(limit: 3, language: 'uk'); // only on a visitor request
```

### Health checks (no credentials needed)

```php
$traffman->health()->common();
$traffman->health()->checkConnection('https://example.com/traffman/health'); // the API calls the URL back
$traffman->health()->checkContentUrl('https://example.com/');
```

### Response models

The resources return immutable models (`Traffman\Sdk\Model`) with typed public properties. Every response model
also keeps its decoded data in `$raw`, including the fields the SDK does not map yet:

```php
$dashboard = $traffman->client()->dashboard();

$dashboard->points->total;               // typed
$dashboard->raw['someNewField'] ?? null; // a field added to the API after the SDK release
```

Create the models with `fromArray()`, e.g. as the return value of a mocked resource interface in your tests:

```php
$iam = ClientIam::fromArray(['id' => '...', 'subscription' => [/* ... */], /* ... */]);
```

The model constructors are `@internal`: a minor version may add arguments to them.

### Endpoints not covered yet

```php
$response = $traffman->api()->request('GET', 'clients/iam', query: ['type' => 'wordpress']);
$data = $response->toArray();
```

## Client confirmation (webhooks)

1. `client()->confirm($callbackUrl)` asks the API to confirm the client for the host of `$callbackUrl`.
2. The API calls `$callbackUrl` with `type: validation` and expects the signature of its token.
3. The API calls `$callbackUrl` again with `type: success` and the client ID. Store the ID: most calls need it.

The SDK handles both calls. Expose the handler on a public route of your platform:

```php
$handler = $traffman->createClientConfirmWebhookHandler(
    'https://example.com/traffman/confirm',                    // the same URL as passed to confirm()
    fn (string $clientId) => $store->set('client_id', $clientId),
);

$response = $handler->handleRaw(file_get_contents('php://input'));

http_response_code($response->statusCode);
foreach ($response->getHeaders() as $name => $value) {
    header("$name: $value");
}
echo $response->getContent();
```

`createHealthConnectionWebhookHandler()` answers `health()->checkConnection()` in the same way.

## Content blocks

The site embeds a script that fills the content blocks on its pages:

```php
$scriptUrl = $traffman->contentBlocks()->buildScriptUrl(
    requestUrl: 'https://example.com/traffman/blocks', // your endpoint the script posts the block IDs to
    blockClass: 'traffman-block',
    formData: ['action' => 'traffman-blocks'],
    language: 'uk',
    extraQuery: ['ver' => '1.0.0'],
);
```

Your endpoint gets the targeted content and signs every item for its block:

```php
$items = $traffman->content()->targeted(count($blockIds), $language)->items;
$blocks = [];

foreach ($blockIds as $index => $blockId) {
    if (!isset($items[$index])) {
        continue;
    }

    $blocks[$blockId] = [
        'id' => $items[$index]->id,
        'content' => htmlspecialchars($items[$index]->content),
        'sig' => $traffman->contentBlocks()->signRedirect($blockId, $items[$index]->id),
    ];
}

echo json_encode(['success' => true, 'blocks' => $blocks]);
```

## Middleware

Middleware wraps every request. It runs in the given order before the request is signed, so it may change the
request freely, and a retried request gets a fresh signature.

```php
use Traffman\Sdk\Cache\Psr16Cache;
use Traffman\Sdk\Middleware\CacheMiddleware;
use Traffman\Sdk\Middleware\CallableMiddleware;
use Traffman\Sdk\Middleware\HeadersMiddleware;
use Traffman\Sdk\Middleware\RetryMiddleware;

new TraffmanClient(credentials: $credentials, middleware: [
    new CallableMiddleware(function ($request, $next) use ($logger) {
        $response = $next->handle($request);
        $logger->info($request->getMethod() . ' ' . $request->getUrl() . ' → ' . $response->getStatusCode());

        return $response;
    }),
    new CacheMiddleware(new Psr16Cache($psr16Cache), ttl: 300), // iam, dashboard, statistics
    new RetryMiddleware(maxRetries: 2),                          // network errors, 429, 502–504
    new HeadersMiddleware(['X-Timezone' => 'Europe/Kyiv']),
]);
```

The middleware can see the operation name in `$request->getAttribute(RequestAttribute::OPERATION)`.
`Http\Operation` lists the names.

## Errors

All SDK exceptions implement `Exception\TraffmanExceptionInterface`.

| Exception | When |
|---|---|
| `TransportException` | No response: network error, timeout, DNS, TLS |
| `ApiException` | Any 4xx/5xx; `getStatusCode()`, `getErrorCode()`, `getPayload()`, `getResponse()` |
| ↳ `BadRequestException` | 400. The confirmation error codes are in `ErrorCode` |
| ↳↳ `ValidationException` | 400 `request.validation.failed`; `getViolations()` |
| ↳ `AuthenticationException` | 401: wrong credentials or signature, or the client is not confirmed or inactive |
| ↳ `AccessDeniedException` | 403 |
| ↳ `NotFoundException` | 404 |
| ↳ `RateLimitException` | 429; `getRetryAfter()` |
| ↳ `ServerException` | 5xx |
| `UnexpectedResponseException` | A 2xx response with an unexpected body |
| `MissingCredentialsException` | The call needs credentials or the client ID, and they are not set |
| `InvalidArgumentException` | Invalid argument, detected before sending |

## Testing your integration

Depend on `TraffmanClientInterface` (or a resource interface) and use `MockTransport`:

```php
use Traffman\Sdk\Testing\MockTransport;

$transport = new MockTransport(MockTransport::json(['items' => ['1', '2']]));
$traffman = new TraffmanClient(credentials: $credentials, transport: $transport);

$traffman->content()->pushedExternalIds(); // ['1', '2']
$transport->getLastRequest()->getUrl();    // .../content/pushed/external-ids
```

## Development

```bash
composer install
composer check   # phpcs + phpstan + phpunit
```

The integration tests (`tests/Integration`) run against a real API only when `TRAFFMAN_API_URL` is set.
They are read-only.

## License

MIT
