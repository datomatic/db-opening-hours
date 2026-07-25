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
