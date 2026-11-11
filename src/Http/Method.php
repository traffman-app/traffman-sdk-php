<?php

declare(strict_types=1);

namespace Traffman\Sdk\Http;

final class Method
{
    public const GET = 'GET';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const PATCH = 'PATCH';
    public const DELETE = 'DELETE';

    private function __construct()
    {
    }
}
