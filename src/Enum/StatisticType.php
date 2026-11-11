<?php

declare(strict_types=1);

namespace Traffman\Sdk\Enum;

enum StatisticType: string
{
    /** Metrics of the client. */
    case CLIENT = 'client';

    /** Metrics of the content pushed by the client. */
    case CONTENT = 'content';
}
