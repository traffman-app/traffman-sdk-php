<?php

declare(strict_types=1);

namespace Traffman\Sdk\Api;

use JsonException;
use Traffman\Sdk\Clock\ClockInterface;
use Traffman\Sdk\Config;
use Traffman\Sdk\Credentials\Credentials;
use Traffman\Sdk\Credentials\CredentialsProviderInterface;
use Traffman\Sdk\Environment\EnvironmentProviderInterface;
use Traffman\Sdk\Exception\InvalidArgumentException;
use Traffman\Sdk\Exception\MissingCredentialsException;
use Traffman\Sdk\Http\Method;
use Traffman\Sdk\Http\Operation;
use Traffman\Sdk\Http\Request;
use Traffman\Sdk\Http\RequestAttribute;
use Traffman\Sdk\Http\RequestHandlerInterface;
use Traffman\Sdk\Http\Response;
use Traffman\Sdk\Middleware\MiddlewareChain;
use Traffman\Sdk\Middleware\MiddlewareInterface;
use Traffman\Sdk\Signature\UrlCanonicalizer;
use Traffman\Sdk\Transport\TransportInterface;
use Traffman\Sdk\UserIp\UserIpResolverInterface;

final class ApiClient implements ApiClientInterface
{
    private const ENVIRONMENT_FIELD = '_env';

    private readonly RequestHandlerInterface $handler;

    /**
     * @param MiddlewareInterface[] $middleware
     */
    public function __construct(
        TransportInterface $transport,
        private readonly CredentialsProviderInterface $credentialsProvider,
        private readonly Config $config,
        private readonly EnvironmentProviderInterface $environmentProvider,
        UserIpResolverInterface $userIpResolver,
        ClockInterface $clock,
        array $middleware = [],
    ) {
        $this->handler = new MiddlewareChain(
            $middleware,
            new SigningHandler($transport, $credentialsProvider, $userIpResolver, $clock),
        );
    }

    public function request(
        string $method,
        string $path,
        array $query = [],
        ?array $json = null,
        bool $authenticated = true,
        string $operation = Operation::CUSTOM,
        array $headers = [],
        array $attributes = [],
    ): Response {
        $method = strtoupper($method);
        $url = $this->config->buildUrl($path);
        $queryString = UrlCanonicalizer::buildQuery(array_filter($query, static fn(mixed $v): bool => $v !== null));

        if ($queryString !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . $queryString;
        }

        $attributes[RequestAttribute::OPERATION] = $operation;
        $attributes[RequestAttribute::AUTHENTICATED] = $authenticated;

        if ($authenticated) {
            $attributes[RequestAttribute::API_KEY] = $this->requireCredentials()->getApiKey();
        }

        $headers = array_merge(
            [
                'Accept' => 'application/json',
                'User-Agent' => $this->config->getUserAgent(),
            ],
            $this->config->headers,
            $headers,
        );

        if ($authenticated && $method !== Method::GET) {
            $json = $this->addEnvironment($json ?? []);
        }

        $body = '';

        if ($json !== null) {
            $body = $this->encodeJson($json);
            $headers['Content-Type'] = 'application/json; charset=UTF-8';
        }

        $response = $this->handler->handle(new Request($method, $url, $headers, $body, $attributes));

        if ($response->getStatusCode() >= 400) {
            throw ApiExceptionFactory::create($response);
        }

        return $response;
    }

    public function getCredentials(): ?Credentials
    {
        return $this->credentialsProvider->getCredentials();
    }

    public function requireCredentials(): Credentials
    {
        return $this->credentialsProvider->getCredentials()
            ?? throw new MissingCredentialsException('The API credentials are not configured.');
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function addEnvironment(array $data): array
    {
        $environment = $this->environmentProvider->getEnvironment();

        if ($environment === null || $environment === []) {
            return $data;
        }

        $data[self::ENVIRONMENT_FIELD] = base64_encode(
            (string) json_encode($environment, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
        );

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function encodeJson(array $data): string
    {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            throw new InvalidArgumentException(
                'The request data cannot be encoded as JSON: ' . $e->getMessage(),
                0,
                $e,
            );
        }
    }
}
