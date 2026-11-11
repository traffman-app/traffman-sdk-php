<?php

declare(strict_types=1);

namespace Traffman\Sdk\Tests\Unit;

use Traffman\Sdk\Enum\ClientType;
use Traffman\Sdk\Enum\ContentLanguageMode;
use Traffman\Sdk\Environment\DefaultEnvironmentProvider;
use Traffman\Sdk\Exception\MissingCredentialsException;
use Traffman\Sdk\Model\SettingsUpdate;
use Traffman\Sdk\Testing\MockTransport;
use Traffman\Sdk\Tests\Support\Fixtures;
use Traffman\Sdk\Tests\Support\ServerSignature;
use Traffman\Sdk\Tests\Support\TestCase;

final class ClientResourceTest extends TestCase
{
    public function testIamSendsSignedRequestAndParsesResponse(): void
    {
        $this->transport->append(MockTransport::json(Fixtures::iam()));

        $iam = $this->createClient()->client()->iam(ClientType::WORDPRESS);

        $request = $this->transport->getLastRequest();
        self::assertNotNull($request);
        self::assertSame('GET', $request->getMethod());
        self::assertSame('https://api.traffman.com/v1/clients/iam?type=wordpress', $request->getUrl());
        self::assertSame('application/json', $request->getHeader('accept'));
        self::assertSame('', $request->getBody());
        self::assertNull($request->getHeader('Content-Type'));

        $authorization = ServerSignature::extractAuthorization($request);
        self::assertNotNull($authorization);
        self::assertSame(self::API_KEY, $authorization['key']);
        self::assertSame(self::TIMESTAMP, $authorization['ts']);
        self::assertTrue(ServerSignature::isRequestValid($request, self::API_SECRET));

        self::assertSame(self::CLIENT_ID, $iam->id);
        self::assertSame('My blog', $iam->description);
        self::assertSame('pro', $iam->subscription->type);
        self::assertSame('month', $iam->subscription->renewal?->interval);
        self::assertSame('2025-01-15', $iam->createdAt->format('Y-m-d'));
        self::assertSame('1.2.0', $iam->version?->preferable);
    }

    public function testIamWithoutTypeHasNoQuery(): void
    {
        $data = Fixtures::iam();
        $data['version'] = null;
        $data['subscription'] = [
            'type' => null,
            'expiresAt' => null,
            'renewal' => null,
            'paymentFailed' => false,
            'resumable' => false,
        ];
        $this->transport->append(MockTransport::json($data));

        $iam = $this->createClient()->client()->iam();

        self::assertSame('https://api.traffman.com/v1/clients/iam', $this->transport->getLastRequest()?->getUrl());
        self::assertNull($iam->version);
        self::assertNull($iam->subscription->type);
        self::assertNull($iam->subscription->renewal);
    }

    public function testDashboard(): void
    {
        $this->transport->append(MockTransport::json(Fixtures::dashboard()));

        $dashboard = $this->createClient()->client()->dashboard();

        self::assertSame(
            'https://api.traffman.com/v1/clients/' . self::CLIENT_ID . '/dashboard',
            $this->transport->getLastRequest()?->getUrl(),
        );
        self::assertSame('Notice 1', $dashboard->notices[0]->message);
        self::assertSame(100.0, $dashboard->points->replenished);
        self::assertSame(109.25, $dashboard->points->total);
        self::assertSame(8, $dashboard->content->valid);
        self::assertSame('First push', $dashboard->achievements[0]->description);
        self::assertSame('Hello', $dashboard->news[0]->title);
    }

    public function testClientScopedCallRequiresClientId(): void
    {
        $this->expectException(MissingCredentialsException::class);

        $this->createClient(self::credentials(null))->client()->dashboard();
    }

    public function testUpdateSettingsSendsOnlyChangedFieldsAndEnvironment(): void
    {
        $this->transport->append(MockTransport::json(null, 204));

        $client = $this->createClient(
            environmentProvider: new DefaultEnvironmentProvider(['cms' => ['name' => 'Test']]),
        );
        $client->client()->updateSettings(new SettingsUpdate(
            contentLanguage: ContentLanguageMode::EXACT,
            blacklistWords: ['casino', 'casino', 'bet'],
        ));

        $request = $this->transport->getLastRequest();
        self::assertNotNull($request);
        self::assertSame('PATCH', $request->getMethod());
        self::assertSame('application/json; charset=UTF-8', $request->getHeader('Content-Type'));
        self::assertTrue(ServerSignature::isRequestValid($request, self::API_SECRET));

        $body = self::decodeBody($request->getBody());
        self::assertSame(1, $body['contentLanguage']);
        self::assertSame(['casino', 'bet'], $body['blacklistWords']);
        self::assertArrayNotHasKey('contentTopicTags', $body);
        self::assertArrayNotHasKey('blacklistDomains', $body);
        self::assertIsString($body['_env']);

        $environment = json_decode(base64_decode($body['_env']), true);
        self::assertIsArray($environment);
        self::assertSame(['name' => 'Test'], $environment['cms']);
        self::assertIsArray($environment['php']);
        self::assertSame(PHP_VERSION, $environment['php']['version'] ?? null);
    }

    public function testConfirmIsAnonymousAndSigned(): void
    {
        $this->transport->append(MockTransport::json(['message' => 'Client confirm request has been accepted.'], 202));

        $callbackUrl = 'https://example.com/wp-json/traffman/client/confirm';
        $result = $this->createClient(self::credentials(null))->client()->confirm($callbackUrl);

        $request = $this->transport->getLastRequest();
        self::assertNotNull($request);
        self::assertSame('https://api.traffman.com/v1/clients/confirm', $request->getUrl());
        self::assertNull($request->getHeader('Authorization'));
        self::assertSame(
            [
                'key' => self::API_KEY,
                'callbackUrl' => $callbackUrl,
                'signature' => ServerSignature::confirmationSignature(self::API_KEY, $callbackUrl, self::API_SECRET),
            ],
            self::decodeBody($request->getBody()),
        );
        self::assertFalse($result->alreadyConfirmed);
        self::assertNull($result->clientId);
    }

    public function testConfirmAlreadyConfirmed(): void
    {
        $this->transport->append(
            MockTransport::json(['id' => self::CLIENT_ID, 'message' => 'Client already confirmed.']),
        );

        $result = $this->createClient()->client()->confirm('https://example.com/callback');

        self::assertTrue($result->alreadyConfirmed);
        self::assertSame(self::CLIENT_ID, $result->clientId);
    }
}
