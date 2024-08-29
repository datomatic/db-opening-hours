<?php

namespace Datomatic\DatabaseOpeningHours\Traits;

use Carbon\Carbon;
use DateTimeInterface;
use Datomatic\DatabaseOpeningHours\Models\OpeningHour;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|static openAt(string|DateTimeInterface $date)
 * @method static \Illuminate\Database\Eloquent\Builder|static closeAt(string|DateTimeInterface $date)
 */
trait HasOpeningHours
{
    public function openingHours(): MorphMany
    {
        return $this->morphMany(OpeningHour::class, 'openable');
    }

    public function latestOpeningHours(): MorphOne
    {
        return $this->morphOne(OpeningHour::class, 'openable')->latestOfMany();
    }

    public function oldestOpeningHours(): MorphOne
    {
        return $this->morphOne(OpeningHour::class, 'openable')->oldestOfMany();
    }

    public function scopeOpenAt(Builder $query, string|DateTimeInterface $date): void
    {
        if (! $date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        $query->whereHas('openingHours', static function (Builder $query) use ($date): void {
            $query->openAt($date);
        });
    }

    public function scopeCloseAt(Builder $query, string|DateTimeInterface $date): void
    {
        if (! $date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        $query->has('openingHours')
            ->whereDoesntHave('openingHours', static function (Builder $query) use ($date): void {
                $query->openAt($date);
            });
    }
}
