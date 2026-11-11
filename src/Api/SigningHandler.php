<?php

declare(strict_types=1);

namespace Traffman\Sdk\Api;

use Traffman\Sdk\Clock\ClockInterface;
use Traffman\Sdk\Credentials\CredentialsProviderInterface;
use Traffman\Sdk\Exception\MissingCredentialsException;
use Traffman\Sdk\Http\Request;
use Traffman\Sdk\Http\RequestAttribute;
use Traffman\Sdk\Http\RequestHandlerInterface;
use Traffman\Sdk\Http\Response;
use Traffman\Sdk\Signature\Signer;
use Traffman\Sdk\Transport\TransportInterface;
use Traffman\Sdk\UserIp\UserIpResolverInterface;

/**
 * The last link of the chain: signs the request (when it is authenticated) and sends it with the transport.
 *
 * It runs after all the middleware, so the signature covers the final request and a retried request
 * gets a fresh timestamp.
 *
 * @internal
 */
final class SigningHandler implements RequestHandlerInterface
{
    public const HEADER_AUTHORIZATION = 'Authorization';
    public const HEADER_USER_IP = 'X-User-Client-IP';
    public const HEADER_USER_IP_SIGNATURE = 'X-User-Client-IP-Signature';

    public function __construct(
        private readonly TransportInterface $transport,
        private readonly CredentialsProviderInterface $credentialsProvider,
        private readonly UserIpResolverInterface $userIpResolver,
        private readonly ClockInterface $clock,
    ) {
    }

    public function handle(Request $request): Response
    {
        if ($request->getAttribute(RequestAttribute::AUTHENTICATED) === true) {
            $request = $this->sign($request);
        }

        return $this->transport->send($request);
    }

    private function sign(Request $request): Request
    {
        $credentials = $this->credentialsProvider->getCredentials()
            ?? throw new MissingCredentialsException('The API credentials are not configured.');

        $signer = new Signer($credentials->getApiSecret());

        $userIp = array_key_exists(RequestAttribute::USER_IP, $request->getAttributes())
            ? $request->getAttribute(RequestAttribute::USER_IP)
            : $this->userIpResolver->resolve();

        if (is_string($userIp) && $userIp !== '') {
            $request = $request
                ->withHeader(self::HEADER_USER_IP, $userIp)
                ->withHeader(self::HEADER_USER_IP_SIGNATURE, $signer->signUserIp($userIp));
        }

        $timestamp = $this->clock->now()->getTimestamp();

        return $request->withHeader(
            self::HEADER_AUTHORIZATION,
            sprintf(
                'Signature key=%s,ts=%d,sig=%s',
                $credentials->getApiKey(),
                $timestamp,
                $signer->signRequest($request->getMethod(), $request->getUrl(), $request->getBody(), $timestamp),
            ),
        );
    }
}
