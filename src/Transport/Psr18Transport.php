<?php

declare(strict_types=1);

namespace Traffman\Sdk\Transport;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Traffman\Sdk\Exception\TransportException;
use Traffman\Sdk\Http\Request;
use Traffman\Sdk\Http\Response;

/**
 * Transport over any PSR-18 HTTP client (Guzzle 7, Symfony HttpClient `Psr18Client`, Buzz, ...).
 *
 * Requires `psr/http-client` and `psr/http-factory` (plus their implementations).
 * Note: configure the client not to follow redirects, and set its timeout.
 */
final class Psr18Transport implements TransportInterface
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function send(Request $request): Response
    {
        $psrRequest = $this->requestFactory->createRequest($request->getMethod(), $request->getUrl());

        foreach ($request->getHeaders() as $name => $value) {
            $psrRequest = $psrRequest->withHeader($name, $value);
        }

        if ($request->getBody() !== '') {
            $psrRequest = $psrRequest->withBody($this->streamFactory->createStream($request->getBody()));
        }

        try {
            $psrResponse = $this->client->sendRequest($psrRequest);
        } catch (ClientExceptionInterface $e) {
            throw new TransportException($e->getMessage(), (int) $e->getCode(), $e);
        }

        $headers = [];

        foreach ($psrResponse->getHeaders() as $name => $values) {
            $headers[(string) $name] = $values;
        }

        return new Response($psrResponse->getStatusCode(), $headers, (string) $psrResponse->getBody());
    }
}
