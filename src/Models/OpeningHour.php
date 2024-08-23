<?php

declare(strict_types=1);

namespace Datomatic\DatabaseOpeningHours\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Datomatic\DatabaseOpeningHours\Enums;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\OpeningHours\OpeningHours;


/**
 * @property int $int
 * @property ?string $name
 * @property string $openable_type
 * @property int $openable_id
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static openAt(string|DateTimeInterface $date)
 */
final class OpeningHour extends Model
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

    public function days(): HasMany
    {
        return $this->hasMany(Day::class)
            ->orderBy('day');
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

    private function day(Enums\Day $day): HasOne
    {
        return $this->hasOne(Day::class)
            ->ofMany(
                ['id' => 'MAX'],
                static fn (Builder $query): Builder => $query->where('day', '=', $day),
            );
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(Exception::class)
            ->orderBy('date');
    }

    public function openable(): MorphTo
    {
        return $this->morphTo();
    }

    function scopeOpenAt(Builder $query, Carbon $date): void
    {
        $query->whereHas('exceptions', function (Builder $query) use ($date): void
        {
            /** @var Builder<Exception> $query */
            $query->openAt($date);
        })
            ->orWhere(function (Builder $query) use ($date): void {
                $query->whereDoesntHave('exceptions', function (Builder $query) use ($date): void {
                    $query->whereDate('date', $date);
                })->whereHas('days', function (Builder $query) use ($date): void
                {
                    /** @var  Builder<Day> $query */
                    $query->openAt($date);
                });
            });
    }

    public function openingHours(): OpeningHours
    {
        return OpeningHours::create(
            $this->days() // @phpstan-ignore-line
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
                ->all(),
        );
    }
}
