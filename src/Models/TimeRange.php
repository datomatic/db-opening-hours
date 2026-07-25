<?php

declare(strict_types=1);

namespace Datomatic\DatabaseOpeningHours\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Datomatic\DatabaseOpeningHours\Support\TimeString;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

use function sprintf;

/**
 * @property DateTimeInterface $start
 * @property DateTimeInterface $end
 * @property 'day'|'execption' $time_rangeable_type
 * @property int $time_rangeable_id
 * @property ?string $description
 * @property-read string $notation
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static openAt(string|DateTimeInterface $date)
 * @method static \Illuminate\Database\Eloquent\Builder|static covering(string|DateTimeInterface $start, string|DateTimeInterface $end)
 */
class TimeRange extends Model
{
    protected $table = 'opening_hours_time_ranges';

    protected $fillable = ['start', 'end', 'description'];

    protected $casts = [
        'id' => 'int',
        'start' => 'datetime',
        'end' => 'datetime',
        'description' => 'string',
    ];

    protected $appends = ['notation'];

    /**
     * @return Attribute<non-falsy-string, never>
     */
    protected function notation(): Attribute
    {
        return Attribute::get(fn (): string => sprintf('%s-%s', $this->start->format('H:i'), $this->end->format('H:i')));
    }

    public function scopeOpenAt(Builder $query, Carbon $date): void
    {
        $query->where('start', '<=', $date->toTimeString())
            ->where('end', '>=', $date->toTimeString());
    }

    /**
     * Ranges that contain the whole `$start`-`$end` window.
     *
     * Containment, not overlap: a window is only covered when a single range
     * spans it end to end, so a 10:00-11:30 request is not satisfied by a
     * 09:00-11:00 range.
     */
    public function scopeCovering(Builder $query, string|DateTimeInterface $start, string|DateTimeInterface $end): void
    {
        $query->where('start', '<=', TimeString::normalize($start))
            ->where('end', '>=', TimeString::normalize($end));
    }
}
