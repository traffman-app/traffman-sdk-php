<?php

declare(strict_types=1);

namespace Traffman\Sdk\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Traffman\Sdk\Http\Request;
use Traffman\Sdk\Signature\Signer;
use Traffman\Sdk\Tests\Support\ServerSignature;

final class SignerTest extends TestCase
{
    public function testSignIsBase64UrlWithoutPadding(): void
    {
        $signature = (new Signer('secret'))->sign('data');

        self::assertSame(ServerSignature::sign('data', 'secret'), $signature);
        self::assertMatchesRegularExpression('/^[a-zA-Z0-9_-]{43}$/', $signature);
    }

    public function testVerify(): void
    {
        $signer = new Signer('secret');

        self::assertTrue($signer->verify('data', $signer->sign('data')));
        self::assertFalse($signer->verify('data', $signer->sign('other')));
    }

    public function testRequestSignatureMatchesServerWhateverTheQueryOrder(): void
    {
        $url = 'https://api.traffman.com/v1/clients/x/content/statistics?type=client&period=week&a%20b=c+d';
        $request = new Request('GET', $url);
        $signature = (new Signer('secret'))->signRequest('GET', $url, '', 1_767_225_600);

        $request = $request->withHeader(
            'Authorization',
            sprintf('Signature key=abc,ts=%d,sig=%s', 1_767_225_600, $signature),
        );

        self::assertTrue(ServerSignature::isRequestValid($request, 'secret'));
        self::assertSame(
            $signature,
            (new Signer('secret'))->signRequest(
                'get',
                'https://api.traffman.com/v1/clients/x/content/statistics?a%20b=c%20d&period=week&type=client',
                '',
                1_767_225_600,
            ),
        );
    }

    public function testRequestSignatureCoversTheBody(): void
    {
        $signer = new Signer('secret');
        $url = 'https://api.traffman.com/v1/clients/x/settings';

        self::assertNotSame(
            $signer->signRequest('PATCH', $url, '{"a":1}', 1_767_225_600),
            $signer->signRequest('PATCH', $url, '{"a":2}', 1_767_225_600),
        );
    }

    public function testConfirmationSignatureUsesEscapedSlashesLikeTheApi(): void
    {
        $callbackUrl = 'https://example.com/wp-json/traffman/client/confirm';

        self::assertSame(
            ServerSignature::sign(
                '{"key":"abc","callbackUrl":"https:\/\/example.com\/wp-json\/traffman\/client\/confirm"}',
                'secret',
            ),
            (new Signer('secret'))->signConfirmation('abc', $callbackUrl),
        );
    }

    public function testContentRedirectSignature(): void
    {
        self::assertSame(
            ServerSignature::sign("block12345\nredirect-id", 'secret'),
            (new Signer('secret'))->signContentRedirect('block12345', 'redirect-id'),
        );
    }
}
