<?php

declare(strict_types=1);

namespace Traffman\Sdk\Tests\Support;

/**
 * Responses in the shape returned by the API.
 */
final class Fixtures
{
    /**
     * @return array<string, mixed>
     */
    public static function iam(): array
    {
        return [
            'id' => '0195a7c2-1f2e-7a3b-9c4d-5e6f7a8b9c0d',
            'description' => 'My blog',
            'subscription' => [
                'type' => 'pro',
                'expiresAt' => '2026-12-31T23:59:59+00:00',
                'renewal' => ['interval' => 'month', 'renewsAt' => '2026-12-01T00:00:00+00:00'],
                'paymentFailed' => false,
                'resumable' => false,
            ],
            'createdAt' => '2025-01-15T10:00:00+00:00',
            'version' => ['required' => '1.0.0', 'preferable' => '1.2.0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function dashboard(): array
    {
        return [
            'notices' => [['type' => 'info', 'message' => 'Notice 1']],
            'points' => ['replenished' => 100, 'earned' => 12.5, 'spent' => 3.25, 'total' => 109.25],
            'content' => ['total' => 10, 'queued' => 1, 'valid' => 8, 'invalid' => 1],
            'achievements' => [
                [
                    'description' => 'First push',
                    'image' => 'https://cdn/a.png',
                    'createdAt' => '2025-02-01T00:00:00+00:00',
                ],
            ],
            'news' => [
                [
                    'title' => 'Hello',
                    'excerpt' => 'World',
                    'url' => 'https://traffman.com/news/hello',
                    'createdAt' => '2025-03-01T00:00:00+00:00',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function chart(): array
    {
        return [
            'period' => 'week',
            'valuesMap' => ['2026-01-01T00:00:00+00:00', '2026-01-02T00:00:00+00:00'],
            'datasets' => [
                ['name' => 2, 'values' => [10, 20]],
                ['name' => 3, 'values' => [1, 4]],
                ['name' => 'ctr', 'values' => [10, 20]],
            ],
            'detailsUrl' => 'https://traffman.com/statistics/client',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function pushResult(): array
    {
        return [
            'counts' => ['total' => 2, 'inserted' => 1, 'updated' => 0, 'removed' => 0, 'skipped' => 0, 'failed' => 1],
            'failed' => [
                [
                    'id' => '2',
                    'violations' => [['path' => 'content', 'message' => 'This value is too short.']],
                ],
            ],
        ];
    }
}
