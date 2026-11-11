<?php

declare(strict_types=1);

namespace Traffman\Sdk\Tests\Unit;

use Traffman\Sdk\Exception\ValidationException;
use Traffman\Sdk\Model\Chart;
use Traffman\Sdk\Model\ClientIam;
use Traffman\Sdk\Model\ConfirmationResult;
use Traffman\Sdk\Testing\MockTransport;
use Traffman\Sdk\Tests\Support\Fixtures;
use Traffman\Sdk\Tests\Support\TestCase;

final class ModelTest extends TestCase
{
    public function testRawKeepsTheFieldsTheSdkDoesNotMap(): void
    {
        $data = array_replace_recursive(Fixtures::iam(), [
            'newField' => ['enabled' => true],
            'subscription' => ['newSubscriptionField' => 42],
        ]);

        $iam = ClientIam::fromArray($data);

        self::assertSame($data, $iam->raw);
        self::assertSame(['enabled' => true], $iam->raw['newField']);
        self::assertSame(42, $iam->subscription->raw['newSubscriptionField']);
        self::assertSame('pro', $iam->subscription->raw['type']);
        self::assertNotNull($iam->version);
        self::assertSame(['required' => '1.0.0', 'preferable' => '1.2.0'], $iam->version->raw);
    }

    public function testRawOfTheListItems(): void
    {
        $data = array_replace_recursive(Fixtures::chart(), ['datasets' => [['color' => '#ff0000']]]);

        $chart = Chart::fromArray($data);

        self::assertSame($data, $chart->raw);
        self::assertSame('#ff0000', $chart->datasets[0]->raw['color']);
        self::assertSame(['name' => 3, 'values' => [1, 4]], $chart->datasets[1]->raw);
    }

    public function testRawOfTheModelsReturnedByResources(): void
    {
        $data = array_replace_recursive(Fixtures::dashboard(), [
            'banner' => 'Black Friday',
            'notices' => [['dismissible' => true]],
        ]);
        $this->transport->append(MockTransport::json($data));

        $dashboard = $this->createClient()->client()->dashboard();

        self::assertSame('Black Friday', $dashboard->raw['banner']);
        self::assertTrue($dashboard->notices[0]->raw['dismissible']);
        self::assertSame(109.25, $dashboard->points->raw['total']);
    }

    public function testRawOfTheModelsWithDerivedFields(): void
    {
        $data = ['message' => 'Already confirmed.', 'id' => self::CLIENT_ID];

        $result = ConfirmationResult::fromArray($data);

        self::assertTrue($result->alreadyConfirmed);
        self::assertSame($data, $result->raw);
    }

    public function testRawOfTheViolations(): void
    {
        $violation = ['path' => 'blacklistWords[0]', 'message' => 'Too short.', 'code' => 'too_short'];
        $this->transport->append(MockTransport::json([
            'code' => 'request.validation.failed',
            'message' => 'Validation failed.',
            'violations' => [$violation],
        ], 400));

        try {
            $this->createClient()->content()->pushedExternalIds();
            self::fail('No exception was thrown.');
        } catch (ValidationException $e) {
            self::assertSame($violation, $e->getViolations()[0]->raw);
        }
    }
}
