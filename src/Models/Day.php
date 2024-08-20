<?php

declare(strict_types=1);

namespace Datomatic\DatabaseOpeningHours\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Datomatic\DatabaseOpeningHours\Enums\Day as DayEnum;

/**
 * @property DayEnum $day
 * @property string $description
 * @property Collection<array-key, TimeRange> $timeRanges
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
}
