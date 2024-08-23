<?php

declare(strict_types=1);

namespace Datomatic\DatabaseOpeningHours\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * @property int $int
 * @property int $opening_hour_id
 * @property ?string $description
 * @property DateTimeInterface $date
 * @property-read  Collection<array-key, TimeRange> $timeRanges
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static openAt(string|DateTimeInterface $date)
 */
final class Exception extends Model
{
    protected $table = 'opening_hours_exceptions';

    protected $fillable = ['date', 'description'];

    protected $casts = [
        'id' => 'int',
        'opening_hour_id' => 'int',
        'date' => 'date',
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
        $query->whereDate('date', $date)
            ->whereHas('timeRanges', function (Builder $query) use ($date): void {
                /** @var Builder<TimeRange> $query */
                $query->openAt($date);
            });
    }
}
