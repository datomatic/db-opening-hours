<?php

declare(strict_types=1);

use Datomatic\DatabaseOpeningHours\Models\Day;
use Datomatic\DatabaseOpeningHours\Models\Exception;
use Datomatic\DatabaseOpeningHours\Models\OpeningHour;
use Datomatic\DatabaseOpeningHours\Models\TimeRange;

// config for Datomatic/DatabaseOpeningHours
return [

    /*
     * Every internal relation resolves its model through this map, so a host
     * application can point any role at a subclass (for example to add tenant
     * scoping or extra columns) without forking the package. A subclass must
     * extend the package model it replaces and keep its table name.
     */
    'models' => [
        'opening_hour' => OpeningHour::class,
        'day' => Day::class,
        'exception' => Exception::class,
        'time_range' => TimeRange::class,
    ],

];
