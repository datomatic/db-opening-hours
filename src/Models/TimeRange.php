<?php

declare(strict_types=1);

namespace Datomatic\DatabaseOpeningHours\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * @property DateTimeInterface $start
 * @property DateTimeInterface $end
 * @property 'day'|'execption' $time_rangeable_type
 * @property int $time_rangeable_id
 * @property ?string $description
 * @property-read string $notation
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static openAt(string|DateTimeInterface $date)
 */
final class TimeRange extends Model
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

    public function notation(): Attribute
    {
        return Attribute::get(function (): string {
            return sprintf('%s-%s', $this->start->format('H:i'), $this->end->format('H:i'));
        });
    }

    public function scopeOpenAt(Builder $query, Carbon $date): void
    {
        $query->where('start', '<=', $date->toTimeString())
            ->where('end', '>=', $date->toTimeString());
    }
}
