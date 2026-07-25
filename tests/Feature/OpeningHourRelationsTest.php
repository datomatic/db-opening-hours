<?php

declare(strict_types=1);

use Datomatic\DatabaseOpeningHours\Enums\Day as DayEnum;
use Datomatic\DatabaseOpeningHours\Models\Day;
use Datomatic\DatabaseOpeningHours\Models\TimeRange;
use Workbench\App\Models\Location;

it('orders days by weekday', function (): void {
    $openingHour = createOpeningHour();
    addDay($openingHour, DayEnum::WEDNESDAY);
    addDay($openingHour, DayEnum::MONDAY);
    addDay($openingHour, DayEnum::FRIDAY);

    expect($openingHour->days()->get()->pluck('day')->all())->toBe([
        DayEnum::MONDAY,
        DayEnum::WEDNESDAY,
        DayEnum::FRIDAY,
    ]);
});

it('exposes each weekday through its named relation', function (): void {
    $openingHour = createOpeningHour();
    addDay($openingHour, DayEnum::MONDAY, [['09:00', '13:00']]);

    expect($openingHour->monday)->toBeInstanceOf(Day::class)
        ->and($openingHour->monday->day)->toBe(DayEnum::MONDAY)
        ->and($openingHour->tuesday)->toBeNull();
});

it('orders time ranges by start then end', function (): void {
    $openingHour = createOpeningHour();
    $day = addDay($openingHour, DayEnum::MONDAY, [
        ['14:00', '18:00'],
        ['09:00', '13:00'],
    ]);

    expect($day->timeRanges->map(fn (TimeRange $range): string => $range->notation)->all())
        ->toBe(['09:00-13:00', '14:00-18:00']);
});

it('exposes the notation accessor', function (): void {
    $openingHour = createOpeningHour();
    $day = addDay($openingHour, DayEnum::MONDAY, [['09:05', '13:30']]);

    expect($day->timeRanges->first()->notation)->toBe('09:05-13:30')
        ->and($day->timeRanges->first()->toArray())->toHaveKey('notation', '09:05-13:30');
});

it('resolves the openable morph relation', function (): void {
    $location = Location::create(['name' => 'Shop']);
    $openingHour = $location->openingHours()->create(['name' => 'default']);

    expect($openingHour->openable)->toBeInstanceOf(Location::class)
        ->and($openingHour->openable->is($location))->toBeTrue();
});
