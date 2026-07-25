<?php

declare(strict_types=1);

namespace Datomatic\DatabaseOpeningHours\Support;

use Datomatic\DatabaseOpeningHours\Models\Day;
use Datomatic\DatabaseOpeningHours\Models\Exception;
use Datomatic\DatabaseOpeningHours\Models\OpeningHour;
use Datomatic\DatabaseOpeningHours\Models\TimeRange;

/**
 * Resolves the model class for each role from config, so a host application can
 * swap any of them for a subclass (e.g. to add tenant scoping) without touching
 * the package. All internal relations go through here.
 */
final class Models
{
    /**
     * @return class-string<OpeningHour>
     */
    public static function openingHour(): string
    {
        return config('db-opening-hours.models.opening_hour', OpeningHour::class);
    }

    /**
     * @return class-string<Day>
     */
    public static function day(): string
    {
        return config('db-opening-hours.models.day', Day::class);
    }

    /**
     * @return class-string<Exception>
     */
    public static function exception(): string
    {
        return config('db-opening-hours.models.exception', Exception::class);
    }

    /**
     * @return class-string<TimeRange>
     */
    public static function timeRange(): string
    {
        return config('db-opening-hours.models.time_range', TimeRange::class);
    }
}
