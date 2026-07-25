<?php

declare(strict_types=1);

namespace Datomatic\DatabaseOpeningHours\Tests;

use Datomatic\DatabaseOpeningHours\DatabaseOpeningHoursServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * Testbench < 10 resets this static property in its setUp() but only
     * declares it on a trait a custom base test case bypasses, so a direct
     * subclass must declare it to run under Pest 2 (Laravel 10/11).
     */
    public static $latestResponse;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Datomatic\\DatabaseOpeningHours\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            DatabaseOpeningHoursServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../workbench/database/migrations');
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
    }
}
