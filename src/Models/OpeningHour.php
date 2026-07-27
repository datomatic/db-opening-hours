<?php

declare(strict_types=1);

namespace Datomatic\DatabaseOpeningHours\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Datomatic\DatabaseOpeningHours\Enums;
use Datomatic\DatabaseOpeningHours\Support\Models;
use Datomatic\DatabaseOpeningHours\Support\TimeString;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\OpeningHours\OpeningHours;

use function array_filter;
use function array_values;

/**
 * @property int $int
 * @property ?string $name
 * @property string $openable_type
 * @property int $openable_id
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static openAt(string|DateTimeInterface $date)
 * @method static \Illuminate\Database\Eloquent\Builder|static openBetween(Carbon $date, string|DateTimeInterface $start, string|DateTimeInterface $end)
 */
class OpeningHour extends Model
{
    protected $table = 'opening_hours';

    protected $fillable = ['name'];

    protected $casts = [
        'id' => 'int',
        'openable_type' => 'string',
        'openable_id' => 'int',
        'name' => 'string',
    ];

    protected $with = ['days'];

    /**
     * @return HasMany<Day, $this>
     */
    public function days(): HasMany
    {
        return $this->hasMany(Models::day())
            ->orderByWeekday();
    }

    public function monday(): HasOne
    {
        return $this->day(Enums\Day::MONDAY);
    }

    public function tuesday(): HasOne
    {
        return $this->day(Enums\Day::TUESDAY);
    }

    public function wednesday(): HasOne
    {
        return $this->day(Enums\Day::WEDNESDAY);
    }

    public function thursday(): HasOne
    {
        return $this->day(Enums\Day::THURSDAY);
    }

    public function friday(): HasOne
    {
        return $this->day(Enums\Day::FRIDAY);
    }

    public function saturday(): HasOne
    {
        return $this->day(Enums\Day::SATURDAY);
    }

    public function sunday(): HasOne
    {
        return $this->day(Enums\Day::SUNDAY);
    }

    /**
     * @return HasOne<Day, $this>
     */
    private function day(Enums\Day $day): HasOne
    {
        return $this->hasOne(Models::day())
            ->ofMany(
                ['id' => 'MAX'],
                static fn (Builder $query): Builder => $query->where('day', '=', $day),
            );
    }

    /**
     * @return HasMany<Exception, $this>
     */
    public function exceptions(): HasMany
    {
        return $this->hasMany(Models::exception())
            ->orderBy('date');
    }

    public function openable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeOpenAt(Builder $query, Carbon $date): void
    {
        $query->whereHas('exceptions', function (Builder $query) use ($date): void {
            /** @var Builder<Exception> $query */
            $query->openAt($date);
        })
            ->orWhere(function (Builder $query) use ($date): void {
                $query->whereDoesntHave('exceptions', function (Builder $query) use ($date): void {
                    $query->whereDate('date', $date);
                })->whereHas('days', function (Builder $query) use ($date): void {
                    /** @var Builder<Day> $query */
                    $query->openAt($date);
                });
            });
    }

    /**
     * Schedules covering the whole `$start`-`$end` window on `$date`.
     *
     * A date exception replaces the weekday ranges for that date: when one
     * exists, only its ranges are considered.
     */
    public function scopeOpenBetween(Builder $query, Carbon $date, string|DateTimeInterface $start, string|DateTimeInterface $end): void
    {
        $query->where(function (Builder $query) use ($date, $start, $end): void {
            $query->whereHas('exceptions', function (Builder $query) use ($date, $start, $end): void {
                /** @var Builder<Exception> $query */
                $query->covering($date, $start, $end);
            })
                ->orWhere(function (Builder $query) use ($date, $start, $end): void {
                    $query->whereDoesntHave('exceptions', function (Builder $query) use ($date): void {
                        $query->whereDate('date', $date);
                    })->whereHas('days', function (Builder $query) use ($date, $start, $end): void {
                        /** @var Builder<Day> $query */
                        $query->covering($date, $start, $end);
                    });
                });
        });
    }

    /**
     * The weekly schedule as `['monday' => [['start' => '09:00', 'end' => '13:00'], ...], ...]`.
     *
     * Only weekdays with at least one range appear.
     *
     * @return array<string, list<array{start: string, end: string}>>
     */
    public function weeklySchedule(): array
    {
        return $this->days()
            ->get()
            ->mapWithKeys(static fn (Day $day): array => [
                $day->day->value => $day->timeRanges
                    ->map(static fn (TimeRange $timeRange): array => [
                        'start' => $timeRange->start->format('H:i'),
                        'end' => $timeRange->end->format('H:i'),
                    ])
                    ->values()
                    ->all(),
            ])
            ->filter(static fn (array $ranges): bool => $ranges !== [])
            ->all();
    }

    /**
     * Replaces the whole weekly schedule with the given one.
     *
     * Accepts the shape returned by {@see weeklySchedule()}. Weekdays missing
     * from the payload (or with no ranges) are removed, so the argument is
     * always the complete new state.
     *
     * @param  array<string, list<array{start: string, end: string}>>  $schedule
     */
    public function syncWeeklySchedule(array $schedule): void
    {
        $keptDayIds = [];

        foreach ($schedule as $day => $ranges) {
            $ranges = array_values(array_filter(
                $ranges,
                static fn (array $range): bool => $range['start'] !== '' && $range['end'] !== '',
            ));

            if ($ranges === []) {
                continue;
            }

            $dayModel = $this->days()->firstOrCreate(['day' => Enums\Day::from($day)]);
            $keptDayIds[] = $dayModel->id;

            $dayModel->timeRanges()->delete();

            foreach ($ranges as $range) {
                $dayModel->timeRanges()->create([
                    'start' => TimeString::normalize($range['start']),
                    'end' => TimeString::normalize($range['end']),
                ]);
            }
        }

        $removedDays = $this->days()->whereNotIn('id', $keptDayIds)->get();

        foreach ($removedDays as $removedDay) {
            $removedDay->timeRanges()->delete();
            $removedDay->delete();
        }

        $this->unsetRelation('days');
    }

    /**
     * The full schedule as a `spatie/opening-hours` object.
     *
     * Both the weekly days and the date exceptions are fed in, so the returned
     * object's date-aware methods (`forDate()`, `isOpenAt()`, `nextOpen()`, …)
     * honour exceptions the same way the `openAt`/`openBetween` query scopes do.
     * A date exception with no ranges maps to a closed day.
     */
    public function openingHours(): OpeningHours
    {
        $schedule = $this->days()
            ->get()
            ->mapWithKeys(static fn (Day $day): array => [
                $day->day->value => array_filter([
                    'data' => $day->description,
                    'hours' => $day->timeRanges
                        ->map(static fn (TimeRange $timeRange): array => array_filter([
                            'data' => $timeRange->description,
                            'hours' => $timeRange->notation,
                        ]))
                        ->all(),
                ]),
            ])
            ->all();

        $exceptions = $this->exceptions()
            ->get()
            ->mapWithKeys(static fn (Exception $exception): array => [
                $exception->date->format('Y-m-d') => array_filter([
                    'data' => $exception->description,
                    'hours' => $exception->timeRanges
                        ->map(static fn (TimeRange $timeRange): array => array_filter([
                            'data' => $timeRange->description,
                            'hours' => $timeRange->notation,
                        ]))
                        ->all(),
                ]),
            ])
            ->all();

        if ($exceptions !== []) {
            $schedule['exceptions'] = $exceptions;
        }

        return OpeningHours::create($schedule);
    }
}
