<?php

declare(strict_types=1);

use Datomatic\DatabaseOpeningHours\Enums\Day as DayEnum;
use Spatie\OpeningHours\OpeningHours;

it('builds a spatie opening hours object from the weekly days', function (): void {
    $openingHour = createOpeningHour();
    addDay($openingHour, DayEnum::MONDAY, [['09:00', '13:00'], ['14:00', '18:00']]);

    $hours = $openingHour->openingHours();

    expect($hours)->toBeInstanceOf(OpeningHours::class)
        ->and($hours->isOpenAt(new DateTimeImmutable('2024-01-01 10:00')))->toBeTrue()
        ->and($hours->isOpenAt(new DateTimeImmutable('2024-01-01 13:30')))->toBeFalse()
        ->and($hours->isOpenAt(new DateTimeImmutable('2024-01-01 15:00')))->toBeTrue();
});

it('treats weekdays without ranges as closed', function (): void {
    $openingHour = createOpeningHour();
    addDay($openingHour, DayEnum::MONDAY, [['09:00', '13:00']]);

    $hours = $openingHour->openingHours();

    // Tuesday 2024-01-02 has no range.
    expect($hours->isOpenAt(new DateTimeImmutable('2024-01-02 10:00')))->toBeFalse();
});

it('lets a date exception override the weekday hours', function (): void {
    $openingHour = createOpeningHour();
    addDay($openingHour, DayEnum::WEDNESDAY, [['09:00', '12:00']]);
    // 2024-01-03 is a Wednesday; the exception replaces the weekday hours.
    addException($openingHour, '2024-01-03', [['15:00', '18:00']]);

    $hours = $openingHour->openingHours();

    // Covered only by the exception.
    expect($hours->isOpenAt(new DateTimeImmutable('2024-01-03 16:00')))->toBeTrue()
        // Weekday hours are suppressed on the exception date.
        ->and($hours->isOpenAt(new DateTimeImmutable('2024-01-03 10:00')))->toBeFalse()
        // A regular Wednesday still uses the weekday hours.
        ->and($hours->isOpenAt(new DateTimeImmutable('2024-01-10 10:00')))->toBeTrue();
});

it('treats a date exception without ranges as a closed day', function (): void {
    $openingHour = createOpeningHour();
    addDay($openingHour, DayEnum::THURSDAY, [['09:00', '18:00']]);
    // 2024-01-04 is a Thursday closed for a holiday.
    addException($openingHour, '2024-01-04');

    $hours = $openingHour->openingHours();

    expect($hours->isOpenAt(new DateTimeImmutable('2024-01-04 10:00')))->toBeFalse()
        ->and($hours->forDate(new DateTimeImmutable('2024-01-04')))->toHaveCount(0);
});

it('returns the exception ranges through forDate', function (): void {
    $openingHour = createOpeningHour();
    addException($openingHour, '2024-12-25', [['10:00', '12:00']], 'Christmas');

    $ranges = $openingHour->openingHours()->forDate(new DateTimeImmutable('2024-12-25'));

    expect($ranges->data)->toBe('Christmas')
        ->and((string) $ranges[0])->toBe('10:00-12:00');
});

it('carries the range descriptions into the spatie object data', function (): void {
    $openingHour = createOpeningHour();
    $day = addDay($openingHour, DayEnum::MONDAY, [], 'Weekly note');
    $day->timeRanges()->create([
        'start' => '09:00',
        'end' => '13:00',
        'description' => 'Morning shift',
    ]);

    $ranges = $openingHour->openingHours()->forDay('monday');

    expect($ranges->data)->toBe('Weekly note')
        ->and((string) $ranges[0])->toBe('09:00-13:00')
        ->and($ranges[0]->data)->toBe('Morning shift');
});
