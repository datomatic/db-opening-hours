<?php

declare(strict_types=1);

namespace Datomatic\DatabaseOpeningHours\Database\Factories;

use Datomatic\DatabaseOpeningHours\Enums;
use Datomatic\DatabaseOpeningHours\Models\Day;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class DayFactory extends Factory
{
    protected $model = Day::class;

    public function definition(): array
    {
        return [
            'opening_hour_id' => OpeningHourFactory::new(),
            'day' => $this->faker->randomElement(Enums\Day::cases()),
            'description' => Str::limit($this->faker->text(), 250),
        ];
    }
}
