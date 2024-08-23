<?php

namespace Datomatic\DatabaseOpeningHours;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
                '100000_create_opening_hours_table',
                '100001_create_opening_hours_days_table',
                '100002_create_opening_hours_exceptions_table',
                '100003_create_opening_hours_time_ranges_table',
            ])
            ->runsMigrations();
    }
}
