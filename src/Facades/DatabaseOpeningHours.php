<?php

namespace Datomatic\DatabaseOpeningHours\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Datomatic\DatabaseOpeningHours\DatabaseOpeningHours
 */
class DatabaseOpeningHours extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Datomatic\DatabaseOpeningHours\DatabaseOpeningHours::class;
    }
}
