<?php

declare(strict_types=1);

use Carbon\Carbon;
use Datomatic\DatabaseOpeningHours\Enums\Day as DayEnum;
use Datomatic\DatabaseOpeningHours\Models\OpeningHour;
use Workbench\App\Models\Location;

function locationWithMonday(string $name): Location
{
    $location = Location::create(['name' => $name]);
    $openingHour = $location->openingHours()->create(['name' => 'default']);
    addDay($openingHour, DayEnum::MONDAY, [['09:00', '13:00']]);

    return $location;
}

it('exposes the opening hours morph relations', function (): void {
    $location = Location::create(['name' => 'Shop']);
    $first = $location->openingHours()->create(['name' => 'first']);
    $second = $location->openingHours()->create(['name' => 'second']);

    expect($location->openingHours)->toHaveCount(2)
        ->and($location->openingHours->first())->toBeInstanceOf(OpeningHour::class)
        ->and($location->latestOpeningHours->is($second))->toBeTrue()
        ->and($location->oldestOpeningHours->is($first))->toBeTrue();
});

it('scopes locations open at an instant', function (): void {
    $open = locationWithMonday('Open');
    Location::create(['name' => 'No hours']);

    expect(Location::query()->openAt(Carbon::parse('2024-01-01 10:00'))->pluck('id')->all())
        ->toBe([$open->id]);
});

it('accepts a plain date string in the open scope', function (): void {
    $open = locationWithMonday('Open');

    expect(Location::query()->openAt('2024-01-01 10:00')->count())->toBe(1);
});

it('scopes locations closed at an instant but excludes those without hours', function (): void {
    $scheduled = locationWithMonday('Scheduled');
    Location::create(['name' => 'No hours']);

    // Monday 20:00: the scheduled location has hours but none open now.
    $result = Location::query()->closeAt(Carbon::parse('2024-01-01 20:00'))->pluck('id')->all();

    expect($result)->toBe([$scheduled->id]);
});

it('scopes locations open across a whole window', function (): void {
    $location = Location::create(['name' => 'Wide']);
    $openingHour = $location->openingHours()->create(['name' => 'default']);
    addDay($openingHour, DayEnum::MONDAY, [['09:00', '18:00']]);

    expect(Location::query()->openBetween('2024-01-01', '10:00', '16:00')->count())->toBe(1)
        ->and(Location::query()->openBetween('2024-01-01', '10:00', '19:00')->count())->toBe(0);
});

it('scopes locations closed across a window but excludes those without hours', function (): void {
    $scheduled = Location::create(['name' => 'Scheduled']);
    $openingHour = $scheduled->openingHours()->create(['name' => 'default']);
    addDay($openingHour, DayEnum::MONDAY, [['09:00', '12:00']]);
    Location::create(['name' => 'No hours']);

    // 10:00-16:00 is not fully covered by 09:00-12:00.
    expect(Location::query()->closedBetween('2024-01-01', '10:00', '16:00')->pluck('id')->all())
        ->toBe([$scheduled->id]);
});
