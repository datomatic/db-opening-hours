<?php

namespace Datomatic\DatabaseOpeningHours;

use Datomatic\DatabaseOpeningHours\Models\Day;
use Datomatic\DatabaseOpeningHours\Models\Exception;
use Illuminate\Database\Eloquent\Relations\Relation;
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
            ->hasTranslations()
            ->hasMigrations([
                '100000_create_opening_hours_table',
                '100001_create_opening_hours_days_table',
                '100002_create_opening_hours_exceptions_table',
                '100003_create_opening_hours_time_ranges_table',
            ])
            ->runsMigrations();
    }

    public function packageRegistered(): void
    {
        Relation::morphMap([
            'day' => Day::class,
            'exception' => Exception::class,
        ]);
    }
}
