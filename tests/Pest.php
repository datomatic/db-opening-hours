<?php

declare(strict_types=1);

use Datomatic\DatabaseOpeningHours\Database\Factories\OpeningHourFactory;
use Datomatic\DatabaseOpeningHours\Enums\Day as DayEnum;
use Datomatic\DatabaseOpeningHours\Models\Day;
use Datomatic\DatabaseOpeningHours\Models\Exception;
use Datomatic\DatabaseOpeningHours\Models\OpeningHour;
use Datomatic\DatabaseOpeningHours\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function createOpeningHour(array $attributes = []): OpeningHour
{
    return OpeningHourFactory::new()->create($attributes);
}

/**
 * @param  list<array{0: string, 1: string}>  $ranges
 */
function addDay(OpeningHour $openingHour, DayEnum $day, array $ranges = [], ?string $description = null): Day
{
    /** @var Day $dayModel */
    $dayModel = $openingHour->days()->create([
        'day' => $day,
        'description' => $description,
    ]);

    foreach ($ranges as [$start, $end]) {
        $dayModel->timeRanges()->create([
            'start' => $start,
            'end' => $end,
        ]);
    }

    return $dayModel->refresh();
}

/**
 * @param  list<array{0: string, 1: string}>  $ranges
 */
function addException(OpeningHour $openingHour, string $date, array $ranges = [], ?string $description = null): Exception
{
    /** @var Exception $exception */
    $exception = $openingHour->exceptions()->create([
        'date' => $date,
        'description' => $description,
    ]);

    foreach ($ranges as [$start, $end]) {
        $exception->timeRanges()->create([
            'start' => $start,
            'end' => $end,
        ]);
    }

    return $exception->refresh();
}
