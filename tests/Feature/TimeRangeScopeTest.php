<?php

declare(strict_types=1);

use Carbon\Carbon;
use Datomatic\DatabaseOpeningHours\Enums\Day as DayEnum;
use Datomatic\DatabaseOpeningHours\Models\TimeRange;

beforeEach(function (): void {
    $openingHour = createOpeningHour();
    // Monday 09:00-13:00 and 14:00-18:00
    addDay($openingHour, DayEnum::MONDAY, [['09:00', '13:00'], ['14:00', '18:00']]);
});

it('matches ranges open at a given instant', function (): void {
    expect(TimeRange::query()->openAt(Carbon::parse('2024-01-01 10:00'))->count())->toBe(1)
        ->and(TimeRange::query()->openAt(Carbon::parse('2024-01-01 15:30'))->count())->toBe(1);
});

it('excludes ranges closed at a given instant', function (): void {
    expect(TimeRange::query()->openAt(Carbon::parse('2024-01-01 13:30'))->count())->toBe(0)
        ->and(TimeRange::query()->openAt(Carbon::parse('2024-01-01 20:00'))->count())->toBe(0);
});

it('matches only ranges that fully contain the window', function (): void {
    expect(TimeRange::query()->covering('10:00', '12:00')->count())->toBe(1)
        ->and(TimeRange::query()->covering('09:00', '13:00')->count())->toBe(1);
});

it('rejects windows a single range does not fully span', function (): void {
    // 12:00-15:00 crosses the lunch gap: no single range covers it.
    expect(TimeRange::query()->covering('12:00', '15:00')->count())->toBe(0)
        ->and(TimeRange::query()->covering('08:00', '10:00')->count())->toBe(0);
});
