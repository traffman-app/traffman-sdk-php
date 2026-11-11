<?php

declare(strict_types=1);

namespace Traffman\Sdk\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Traffman\Sdk\Content\ContentNormalizer;

final class ContentNormalizerTest extends TestCase
{
    public function testNormalize(): void
    {
        $html = "<h1>Title</h1><p>First&nbsp;line &amp; more</p>\n\n<script>alert(1)</script><p>Second</p>"
            . "<style>p{}</style>Привіт\xB1";

        self::assertSame(
            'Title First line & more Second Привіт?',
            (new ContentNormalizer())->normalize($html),
        );
    }

    public function testTruncatesByCharacters(): void
    {
        $normalizer = new ContentNormalizer();

        self::assertSame('Приві', $normalizer->normalize('Привіт світ', 5));
        self::assertSame(255, mb_strlen($normalizer->normalizeTitle(str_repeat('я', 300))));
        self::assertFalse($normalizer->isContentLongEnough('short'));
        self::assertTrue($normalizer->isContentLongEnough(str_repeat('a', 128)));
    }
}
