<?php

declare(strict_types=1);

namespace Datomatic\DatabaseOpeningHours\Traits;

use Carbon\Carbon;
use DateTimeInterface;
use Datomatic\DatabaseOpeningHours\Models\OpeningHour;
use Datomatic\DatabaseOpeningHours\Support\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|static openAt(string|DateTimeInterface $date)
 * @method static \Illuminate\Database\Eloquent\Builder|static closeAt(string|DateTimeInterface $date)
 * @method static \Illuminate\Database\Eloquent\Builder|static openBetween(string|DateTimeInterface $date, string|DateTimeInterface $start, string|DateTimeInterface $end)
 * @method static \Illuminate\Database\Eloquent\Builder|static closedBetween(string|DateTimeInterface $date, string|DateTimeInterface $start, string|DateTimeInterface $end)
 */
trait HasOpeningHours
{
    public function openingHours(): MorphMany
    {
        return $this->morphMany(Models::openingHour(), 'openable');
    }

    public function latestOpeningHours(): MorphOne
    {
        return $this->morphOne(Models::openingHour(), 'openable')->latestOfMany();
    }

    public function oldestOpeningHours(): MorphOne
    {
        return $this->morphOne(Models::openingHour(), 'openable')->oldestOfMany();
    }

    public function scopeOpenAt(Builder $query, string|DateTimeInterface $date): void
    {
        if (! $date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        $query->whereHas('openingHours', static function (Builder $query) use ($date): void {
            /** @var Builder<OpeningHour> $query */
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
                /** @var Builder<OpeningHour> $query */
                $query->openAt($date);
            });
    }

    /**
     * Records whose opening hours fully cover `$start`-`$end` on `$date`.
     */
    public function scopeOpenBetween(Builder $query, string|DateTimeInterface $date, string|DateTimeInterface $start, string|DateTimeInterface $end): void
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        $query->whereHas('openingHours', static function (Builder $query) use ($date, $start, $end): void {
            /** @var Builder<OpeningHour> $query */
            $query->openBetween($date, $start, $end);
        });
    }

    /**
     * Records that have opening hours but none covering `$start`-`$end` on `$date`.
     *
     * Records without any schedule are deliberately excluded: no schedule means
     * "no restriction", not "always closed".
     */
    public function scopeClosedBetween(Builder $query, string|DateTimeInterface $date, string|DateTimeInterface $start, string|DateTimeInterface $end): void
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        $query->has('openingHours')
            ->whereDoesntHave('openingHours', static function (Builder $query) use ($date, $start, $end): void {
                /** @var Builder<OpeningHour> $query */
                $query->openBetween($date, $start, $end);
            });
    }
}
