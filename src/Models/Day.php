<?php

declare(strict_types=1);

namespace Datomatic\DatabaseOpeningHours\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Datomatic\DatabaseOpeningHours\Enums\Day as DayEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * @property int $int
 * @property DayEnum $day
 * @property ?string $description
 * @property-read Collection<array-key, TimeRange> $timeRanges
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static openAt(string|DateTimeInterface $date)
 */
final class Day extends Model
{
    protected $table = 'opening_hours_days';

    protected $fillable = ['day', 'description'];

    protected $casts = [
        'id' => 'int',
        'opening_hour_id' => 'int',
        'day' => DayEnum::class,
        'description' => 'string',
    ];

    protected $with = ['timeRanges'];

    public function timeRanges(): MorphMany
    {
        return $this->morphMany(TimeRange::class, 'time_rangeable')
            ->orderBy('start')
            ->orderBy('end');
    }

    public function scopeOpenAt(Builder $query, Carbon $date): void
    {
        $query->where('day', DayEnum::from(strtolower($date->locale('en')->dayName)))
            ->whereHas('timeRanges', function (Builder $query) use ($date): void {
                /** @var Builder<TimeRange> $query */
                $query->openAt($date);
            });
    }
}
