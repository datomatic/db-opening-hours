<?php

namespace Datomatic\DatabaseOpeningHours;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Datomatic\DatabaseOpeningHours\Commands\DatabaseOpeningHoursCommand;

class DatabaseOpeningHoursServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('db-opening-hours')
            ->hasConfigFile()
            ->hasMigrations([
                'create_opening_hours_days_table',
                'create_opening_hours_exceptions_table',
                'create_opening_hours_table',
                'create_opening_hours_time_ranges_table'
            ])
            ->runsMigrations();
    }
}
