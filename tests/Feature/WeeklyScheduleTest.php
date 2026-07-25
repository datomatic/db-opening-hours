<?php

declare(strict_types=1);

use Datomatic\DatabaseOpeningHours\Enums\Day as DayEnum;
use Datomatic\DatabaseOpeningHours\Models\TimeRange;

it('returns the weekly schedule keyed by weekday', function (): void {
    $openingHour = createOpeningHour();
    addDay($openingHour, DayEnum::MONDAY, [['09:00', '13:00'], ['14:00', '18:00']]);
    addDay($openingHour, DayEnum::TUESDAY, [['10:00', '16:00']]);

    expect($openingHour->weeklySchedule())->toBe([
        'monday' => [
            ['start' => '09:00', 'end' => '13:00'],
            ['start' => '14:00', 'end' => '18:00'],
        ],
        'tuesday' => [
            ['start' => '10:00', 'end' => '16:00'],
        ],
    ]);
});

it('omits weekdays without ranges', function (): void {
    $openingHour = createOpeningHour();
    addDay($openingHour, DayEnum::MONDAY, [['09:00', '13:00']]);
    addDay($openingHour, DayEnum::TUESDAY);

    expect($openingHour->weeklySchedule())->toHaveKeys(['monday'])
        ->not->toHaveKey('tuesday');
});

it('syncs a brand new weekly schedule', function (): void {
    $openingHour = createOpeningHour();

    $openingHour->syncWeeklySchedule([
        'monday' => [['start' => '09:00', 'end' => '13:00']],
        'friday' => [['start' => '08:00', 'end' => '12:00']],
    ]);

    expect($openingHour->weeklySchedule())->toBe([
        'monday' => [['start' => '09:00', 'end' => '13:00']],
        'friday' => [['start' => '08:00', 'end' => '12:00']],
    ]);
});

it('replaces the whole schedule and removes missing weekdays', function (): void {
    $openingHour = createOpeningHour();
    addDay($openingHour, DayEnum::MONDAY, [['09:00', '13:00']]);
    addDay($openingHour, DayEnum::TUESDAY, [['09:00', '13:00']]);

    $openingHour->syncWeeklySchedule([
        'monday' => [['start' => '10:00', 'end' => '14:00']],
    ]);

    expect($openingHour->weeklySchedule())->toBe([
        'monday' => [['start' => '10:00', 'end' => '14:00']],
    ]);

    expect($openingHour->days()->count())->toBe(1);
    expect(TimeRange::count())->toBe(1);
});

it('skips weekdays whose ranges are empty when syncing', function (): void {
    $openingHour = createOpeningHour();

    $openingHour->syncWeeklySchedule([
        'monday' => [['start' => '09:00', 'end' => '13:00']],
        'tuesday' => [],
        'wednesday' => [['start' => '', 'end' => '']],
    ]);

    expect(\array_keys($openingHour->weeklySchedule()))->toBe(['monday']);
});

it('normalizes sync input before persisting', function (): void {
    $openingHour = createOpeningHour();

    $openingHour->syncWeeklySchedule([
        'monday' => [['start' => '9:00', 'end' => '13:00']],
    ]);

    $range = $openingHour->days()->firstOrFail()->timeRanges()->firstOrFail();

    expect($range->start->format('H:i:s'))->toBe('09:00:00')
        ->and($range->notation)->toBe('09:00-13:00');
});
