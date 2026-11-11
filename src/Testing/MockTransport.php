<?php

declare(strict_types=1);

namespace Traffman\Sdk\Testing;

use Closure;
use LogicException;
use Traffman\Sdk\Exception\TransportException;
use Traffman\Sdk\Http\Request;
use Traffman\Sdk\Http\Response;
use Traffman\Sdk\Transport\TransportInterface;

/**
 * Transport for tests: returns the queued responses and records the sent requests.
 */
final class MockTransport implements TransportInterface
{
    /**
     * @var list<Response|TransportException|(Closure(Request): Response)>
     */
    private array $queue = [];

    /**
     * @var list<Request>
     */
    private array $requests = [];

    /**
     * @param Response|TransportException|(Closure(Request): Response) ...$responses
     */
    public function __construct(Response|TransportException|Closure ...$responses)
    {
        foreach ($responses as $response) {
            $this->append($response);
        }
    }

    /**
     * @param array<mixed>|null $data
     * @param array<string, string> $headers
     */
    public static function json(?array $data, int $statusCode = 200, array $headers = []): Response
    {
        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'] + $headers,
            $data !== null ? (string) json_encode($data) : '',
        );
    }

    /**
     * @param Response|TransportException|(Closure(Request): Response) $response
     */
    public function append(Response|TransportException|Closure $response): self
    {
        $this->queue[] = $response;

        return $this;
    }

    public function send(Request $request): Response
    {
        $this->requests[] = $request;

        $response = array_shift($this->queue)
            ?? throw new LogicException(sprintf(
                'No response is queued for the request %s %s.',
                $request->getMethod(),
                $request->getUrl(),
            ));

        if ($response instanceof TransportException) {
            throw $response;
        }

        return $response instanceof Closure ? $response($request) : $response;
    }

    /**
     * @return list<Request>
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    public function getLastRequest(): ?Request
    {
        return $this->requests[count($this->requests) - 1] ?? null;
    }

    public function countPending(): int
    {
        return count($this->queue);
    }
}
