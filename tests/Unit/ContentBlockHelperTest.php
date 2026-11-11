<?php

declare(strict_types=1);

namespace Traffman\Sdk\Tests\Unit;

use Traffman\Sdk\Exception\MissingCredentialsException;
use Traffman\Sdk\Tests\Support\ServerSignature;
use Traffman\Sdk\Tests\Support\TestCase;

final class ContentBlockHelperTest extends TestCase
{
    public function testScriptUrlIsSignedLikeTheApiExpects(): void
    {
        $url = $this->createClient()->contentBlocks()->buildScriptUrl(
            requestUrl: 'https://example.com/wp-admin/admin-ajax.php',
            blockClass: 'traffman_block-content',
            formData: ['action' => 'traffman-block-content-get'],
            language: 'uk',
            extraQuery: ['ver' => '1.2.0'],
        );

        self::assertStringStartsWith('https://api.traffman.com/v1/clients/scripts/content/blocks.js?', $url);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        self::assertSame(self::API_KEY, $query['key']);
        self::assertSame((string) self::TIMESTAMP, $query['ts']);
        self::assertSame(['action' => 'traffman-block-content-get'], $query['formData']);
        self::assertSame('uk', $query['language']);
        self::assertSame('1.2.0', $query['ver']);
        self::assertTrue(ServerSignature::isQueryUrlValid($url, self::API_SECRET));
    }

    public function testScriptUrlWithoutOptionalParameters(): void
    {
        $url = $this->createClient()->contentBlocks()->buildScriptUrl('https://example.com/ajax', 'blocks');

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        self::assertArrayNotHasKey('language', $query);
        self::assertArrayNotHasKey('formData', $query);
        self::assertTrue(ServerSignature::isQueryUrlValid($url, self::API_SECRET));
    }

    public function testRedirect(): void
    {
        $blocks = $this->createClient()->contentBlocks();
        $signature = ServerSignature::sign("abcde12345\nredirect-1", self::API_SECRET);

        self::assertSame($signature, $blocks->signRedirect('abcde12345', 'redirect-1'));
        self::assertSame(
            'https://api.traffman.com/v1/content/redirects/redirect-1/abcde12345/' . $signature,
            $blocks->buildRedirectUrl('abcde12345', 'redirect-1'),
        );
    }

    public function testRequiresCredentials(): void
    {
        $client = new \Traffman\Sdk\TraffmanClient(transport: $this->transport);

        $this->expectException(MissingCredentialsException::class);

        $client->contentBlocks()->signRedirect('abcde12345', 'redirect-1');
    }
}
