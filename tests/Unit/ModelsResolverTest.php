<?php

declare(strict_types=1);

use Datomatic\DatabaseOpeningHours\Models\Day;
use Datomatic\DatabaseOpeningHours\Models\Exception;
use Datomatic\DatabaseOpeningHours\Models\OpeningHour;
use Datomatic\DatabaseOpeningHours\Models\TimeRange;
use Datomatic\DatabaseOpeningHours\Support\Models;

it('resolves the default model for each role', function (): void {
    expect(Models::openingHour())->toBe(OpeningHour::class)
        ->and(Models::day())->toBe(Day::class)
        ->and(Models::exception())->toBe(Exception::class)
        ->and(Models::timeRange())->toBe(TimeRange::class);
});

it('resolves a host overridden model from config', function (): void {
    $custom = new class extends OpeningHour {};

    config()->set('db-opening-hours.models.opening_hour', $custom::class);

    expect(Models::openingHour())->toBe($custom::class);
});
