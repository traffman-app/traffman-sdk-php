<?php

/**
 * The SDK over Guzzle 7 (a PSR-18 client), e.g. in Laravel or Drupal:
 * composer require guzzlehttp/guzzle
 */

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Traffman\Sdk\Credentials\Credentials;
use Traffman\Sdk\Enum\StatisticPeriod;
use Traffman\Sdk\Enum\StatisticType;
use Traffman\Sdk\Exception\ApiException;
use Traffman\Sdk\Exception\TransportException;
use Traffman\Sdk\Transport\Psr18Transport;
use Traffman\Sdk\TraffmanClient;

require __DIR__ . '/../vendor/autoload.php';

$factory = new HttpFactory();

$traffman = new TraffmanClient(
    credentials: new Credentials(
        (string) getenv('TRAFFMAN_API_KEY'),
        (string) getenv('TRAFFMAN_API_SECRET'),
        (string) getenv('TRAFFMAN_CLIENT_ID'),
    ),
    transport: new Psr18Transport(
        new Client(['timeout' => 10, 'allow_redirects' => false, 'http_errors' => false]),
        $factory,
        $factory,
    ),
);

try {
    $chart = $traffman->content()->statistics(StatisticType::CLIENT, StatisticPeriod::MONTH, 'Europe/Kyiv');

    foreach ($chart->datasets as $dataset) {
        printf("%s: %s\n", $dataset->name, implode(', ', $dataset->values));
    }
} catch (ApiException $e) {
    printf("API error %d (%s): %s\n", $e->getStatusCode(), var_export($e->getErrorCode(), true), $e->getMessage());
} catch (TransportException $e) {
    printf("Network error: %s\n", $e->getMessage());
}
