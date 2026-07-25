<?php

declare(strict_types=1);

use Carbon\Carbon;
use Datomatic\DatabaseOpeningHours\Enums\Day as DayEnum;
use Datomatic\DatabaseOpeningHours\Models\Day;
use Datomatic\DatabaseOpeningHours\Models\Exception;

it('matches the day row for the weekday and instant', function (): void {
    $openingHour = createOpeningHour();
    addDay($openingHour, DayEnum::MONDAY, [['09:00', '13:00']]);
    addDay($openingHour, DayEnum::TUESDAY, [['09:00', '13:00']]);

    // Monday 10:00 -> only the monday row.
    expect(Day::query()->openAt(Carbon::parse('2024-01-01 10:00'))->pluck('day')->all())
        ->toBe([DayEnum::MONDAY]);

    // Monday 20:00 -> no row open.
    expect(Day::query()->openAt(Carbon::parse('2024-01-01 20:00'))->count())->toBe(0);
});

it('matches day rows covering a whole window', function (): void {
    $openingHour = createOpeningHour();
    addDay($openingHour, DayEnum::MONDAY, [['09:00', '18:00']]);

    expect(Day::query()->covering(Carbon::parse('2024-01-01'), '10:00', '16:00')->count())->toBe(1)
        ->and(Day::query()->covering(Carbon::parse('2024-01-01'), '10:00', '19:00')->count())->toBe(0);
});

it('matches the exception for its date and instant', function (): void {
    $openingHour = createOpeningHour();
    addException($openingHour, '2024-01-03', [['15:00', '18:00']]);

    expect(Exception::query()->openAt(Carbon::parse('2024-01-03 16:00'))->count())->toBe(1)
        ->and(Exception::query()->openAt(Carbon::parse('2024-01-03 10:00'))->count())->toBe(0)
        ->and(Exception::query()->openAt(Carbon::parse('2024-01-04 16:00'))->count())->toBe(0);
});

it('matches exceptions covering a whole window', function (): void {
    $openingHour = createOpeningHour();
    addException($openingHour, '2024-01-03', [['15:00', '20:00']]);

    expect(Exception::query()->covering(Carbon::parse('2024-01-03'), '16:00', '19:00')->count())->toBe(1)
        ->and(Exception::query()->covering(Carbon::parse('2024-01-03'), '16:00', '21:00')->count())->toBe(0);
});
