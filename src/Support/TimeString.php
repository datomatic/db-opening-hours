<?php

declare(strict_types=1);

namespace Datomatic\DatabaseOpeningHours\Support;

use Carbon\Carbon;
use DateTimeInterface;

final class TimeString
{
    /**
     * Normalises `9:00`, `09:00`, `09:00:00` or a date-time to `09:00:00`, the
     * format the `time` columns are compared against.
     */
    public static function normalize(string|DateTimeInterface $time): string
    {
        if ($time instanceof DateTimeInterface) {
            return $time->format('H:i:s');
        }

        return Carbon::parse($time)->format('H:i:s');
    }
}
