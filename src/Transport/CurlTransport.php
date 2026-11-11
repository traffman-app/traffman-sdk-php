<?php

declare(strict_types=1);

namespace Traffman\Sdk\Transport;

use LogicException;
use Traffman\Sdk\Exception\TransportException;
use Traffman\Sdk\Http\Request;
use Traffman\Sdk\Http\Response;

/**
 * Dependency-free transport over ext-curl.
 */
final class CurlTransport implements TransportInterface
{
    /**
     * @param float $timeout Total timeout of a request, in seconds.
     * @param float $connectTimeout Connection timeout, in seconds.
     * @param array<int, mixed> $curlOptions Extra `CURLOPT_*` options (e.g. a proxy); they override the defaults.
     */
    public function __construct(
        private readonly float $timeout = 10.0,
        private readonly float $connectTimeout = 5.0,
        private readonly array $curlOptions = [],
    ) {
        if (!extension_loaded('curl')) {
            throw new LogicException('CurlTransport requires the "curl" PHP extension.');
        }
    }

    public function send(Request $request): Response
    {
        $handle = curl_init();

        if ($handle === false) {
            throw new TransportException('Failed to initialize a cURL handle.');
        }

        $headers = [];

        foreach ($request->getHeaders() as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }

        // cURL adds `Expect: 100-continue` to large bodies, which delays the request by a round trip.
        $headers[] = 'Expect:';

        $responseHeaders = [];

        $options = [
            CURLOPT_URL => $request->getUrl(),
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT_MS => (int) round($this->timeout * 1000),
            CURLOPT_CONNECTTIMEOUT_MS => (int) round($this->connectTimeout * 1000),
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_ENCODING => '',
            CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$responseHeaders): int {
                $parts = explode(':', $line, 2);

                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))][] = trim($parts[1]);
                } elseif (str_starts_with($line, 'HTTP/')) {
                    // a new response (e.g. after `100 Continue`): only the headers of the last one are kept
                    $responseHeaders = [];
                }

                return strlen($line);
            },
        ];

        if ($request->getBody() !== '') {
            $options[CURLOPT_POSTFIELDS] = $request->getBody();
        } elseif ($request->getMethod() === 'HEAD') {
            $options[CURLOPT_NOBODY] = true;
        }

        curl_setopt_array($handle, $this->curlOptions + $options);

        $body = curl_exec($handle);

        if (!is_string($body)) {
            $message = curl_error($handle);
            $code = curl_errno($handle);

            throw new TransportException(sprintf('cURL error %d: %s', $code, $message), $code);
        }

        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);

        /** @var array<string, string[]> $responseHeaders */
        return new Response($statusCode, $responseHeaders, $body);
    }
}
