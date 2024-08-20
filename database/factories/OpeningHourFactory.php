<?php

declare(strict_types=1);

namespace Datomatic\DatabaseOpeningHours\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Datomatic\DatabaseOpeningHours\Models\OpeningHour;

final class OpeningHourFactory extends Factory
{
    protected $model = OpeningHour::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
        ];
    }
}
