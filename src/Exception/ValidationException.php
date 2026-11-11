<?php

declare(strict_types=1);

namespace Traffman\Sdk\Exception;

use Traffman\Sdk\Http\Response;
use Traffman\Sdk\Model\Violation;

/**
 * HTTP 400 with the code `request.validation.failed` – the request data did not pass the validation.
 */
class ValidationException extends BadRequestException
{
    /**
     * @param Violation[] $violations
     * @param array<mixed> $payload
     */
    public function __construct(
        string $message,
        Response $response,
        int|string|null $errorCode,
        array $payload,
        private readonly array $violations,
    ) {
        parent::__construct($message, $response, $errorCode, $payload);
    }

    /**
     * @return Violation[]
     */
    public function getViolations(): array
    {
        return $this->violations;
    }
}
