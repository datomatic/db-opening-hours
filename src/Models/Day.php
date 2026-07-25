<?php

declare(strict_types=1);

namespace Datomatic\DatabaseOpeningHours\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Datomatic\DatabaseOpeningHours\Enums\Day as DayEnum;
use Datomatic\DatabaseOpeningHours\Support\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

use function strtolower;

/**
 * @property int $int
 * @property DayEnum $day
 * @property ?string $description
 * @property-read Collection<array-key, TimeRange> $timeRanges
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static openAt(string|DateTimeInterface $date)
 * @method static \Illuminate\Database\Eloquent\Builder|static covering(Carbon $date, string|DateTimeInterface $start, string|DateTimeInterface $end)
 */
class Day extends Model
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
        return $this->morphMany(Models::timeRange(), 'time_rangeable')
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

    /**
     * Weekday rows whose ranges cover the whole `$start`-`$end` window.
     */
    public function scopeCovering(Builder $query, Carbon $date, string|DateTimeInterface $start, string|DateTimeInterface $end): void
    {
        $query->where('day', DayEnum::from(strtolower($date->locale('en')->dayName)))
            ->whereHas('timeRanges', function (Builder $query) use ($start, $end): void {
                /** @var Builder<TimeRange> $query */
                $query->covering($start, $end);
            });
    }
}
