<?php

declare(strict_types=1);

use Carbon\Carbon;
use Datomatic\DatabaseOpeningHours\Enums\Day as DayEnum;
use Datomatic\DatabaseOpeningHours\Models\OpeningHour;

it('finds records open at a weekday instant', function (): void {
    $openingHour = createOpeningHour();
    addDay($openingHour, DayEnum::MONDAY, [['09:00', '13:00']]);

    // Monday 2024-01-01 10:00 -> open
    expect(OpeningHour::query()->openAt(Carbon::parse('2024-01-01 10:00'))->pluck('id')->all())
        ->toBe([$openingHour->id]);

    // Monday 14:00 -> closed
    expect(OpeningHour::query()->openAt(Carbon::parse('2024-01-01 14:00'))->count())->toBe(0);

    // Tuesday -> no matching weekday
    expect(OpeningHour::query()->openAt(Carbon::parse('2024-01-02 10:00'))->count())->toBe(0);
});

it('lets a date exception open a record that the weekday would close', function (): void {
    $openingHour = createOpeningHour();
    addDay($openingHour, DayEnum::WEDNESDAY, [['09:00', '12:00']]);
    // Wednesday 2024-01-03 gets a special evening window.
    addException($openingHour, '2024-01-03', [['15:00', '18:00']]);

    // 16:00 only covered by the exception -> open
    expect(OpeningHour::query()->openAt(Carbon::parse('2024-01-03 16:00'))->count())->toBe(1);
});

it('lets a date exception override and close the weekday hours', function (): void {
    $openingHour = createOpeningHour();
    addDay($openingHour, DayEnum::WEDNESDAY, [['09:00', '12:00']]);
    // Exception replaces the weekday: only 15:00-18:00 counts that date.
    addException($openingHour, '2024-01-03', [['15:00', '18:00']]);

    // 10:00 would be open by the weekday rule, but the exception suppresses it.
    expect(OpeningHour::query()->openAt(Carbon::parse('2024-01-03 10:00'))->count())->toBe(0);
});

it('finds records covering a whole window on a weekday', function (): void {
    $openingHour = createOpeningHour();
    addDay($openingHour, DayEnum::MONDAY, [['09:00', '18:00']]);

    expect(OpeningHour::query()->openBetween(Carbon::parse('2024-01-01'), '10:00', '16:00')->count())->toBe(1)
        ->and(OpeningHour::query()->openBetween(Carbon::parse('2024-01-01'), '10:00', '19:00')->count())->toBe(0);
});

it('honours exceptions when covering a window', function (): void {
    $openingHour = createOpeningHour();
    addDay($openingHour, DayEnum::WEDNESDAY, [['09:00', '12:00']]);
    addException($openingHour, '2024-01-03', [['15:00', '20:00']]);

    // Window fully inside the exception -> covered.
    expect(OpeningHour::query()->openBetween(Carbon::parse('2024-01-03'), '16:00', '19:00')->count())->toBe(1);

    // Window inside the weekday hours but suppressed by the exception -> not covered.
    expect(OpeningHour::query()->openBetween(Carbon::parse('2024-01-03'), '09:30', '11:00')->count())->toBe(0);
});
