<?php

declare(strict_types=1);

namespace Traffman\Sdk\Enum;

/**
 * Which language of the targeted content is accepted by the client.
 */
enum ContentLanguageMode: int
{
    /** Content of any language (the requested language is preferred). */
    case ANY = 0;

    /** Content of the requested language only. */
    case EXACT = 1;
}
