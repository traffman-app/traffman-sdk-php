<?php

declare(strict_types=1);

namespace Example\WordPress;

use Traffman\Sdk\Exception\TransportException;
use Traffman\Sdk\Http\Request;
use Traffman\Sdk\Http\Response;
use Traffman\Sdk\Transport\TransportInterface;
use WpOrg\Requests\Utility\CaseInsensitiveDictionary;

/**
 * Transport over the WordPress HTTP API (`wp_remote_request()`), so the SDK needs no extra packages in a plugin.
 */
final class WpHttpTransport implements TransportInterface
{
    public function __construct(
        private readonly int $timeout = 10,
    ) {
    }

    public function send(Request $request): Response
    {
        $result = wp_remote_request($request->getUrl(), [
            'method' => $request->getMethod(),
            'headers' => $request->getHeaders(),
            'body' => $request->getBody() !== '' ? $request->getBody() : null,
            'timeout' => $this->timeout,
            'redirection' => 0,
        ]);

        if (is_wp_error($result)) {
            throw new TransportException($result->get_error_message());
        }

        $headers = wp_remote_retrieve_headers($result);
        $headers = $headers instanceof CaseInsensitiveDictionary ? $headers->getAll() : (array) $headers;

        return new Response(
            (int) wp_remote_retrieve_response_code($result),
            $headers,
            wp_remote_retrieve_body($result),
        );
    }
}
