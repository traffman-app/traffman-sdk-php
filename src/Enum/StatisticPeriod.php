<?php

declare(strict_types=1);

namespace Traffman\Sdk\Enum;

enum StatisticPeriod: string
{
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';
}
