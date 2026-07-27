# Store and query opening hours in the database

[![Latest Version on Packagist](https://img.shields.io/packagist/v/datomatic/db-opening-hours.svg?style=flat-square)](https://packagist.org/packages/datomatic/db-opening-hours)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/datomatic/db-opening-hours/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/datomatic/db-opening-hours/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/datomatic/db-opening-hours/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/datomatic/db-opening-hours/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/datomatic/db-opening-hours.svg?style=flat-square)](https://packagist.org/packages/datomatic/db-opening-hours)

[`spatie/opening-hours`](https://github.com/spatie/opening-hours) is great for describing a weekly schedule in code, but it keeps everything in memory. This package **persists that schedule in your database** and attaches it to any Eloquent model, so you can store opening hours per record, edit them at runtime, and **query them straight from SQL** — "which shops are open right now?", "is this venue open between 10:00 and 12:00 next Monday?" — without loading every model into PHP.

You still get a real `Spatie\OpeningHours\OpeningHours` object whenever you need its full API.

```php
$shop->openingHours()->create(['name' => 'default'])->syncWeeklySchedule([
    'monday' => [['start' => '09:00', 'end' => '13:00'], ['start' => '14:00', 'end' => '18:00']],
    'tuesday' => [['start' => '09:00', 'end' => '18:00']],
]);

// Query from the database
Shop::openAt(now())->get();                          // shops open right now
Shop::openBetween('2024-01-01', '10:00', '12:00')->get();
```

## What it offers

- **Weekly schedule per model** — attach opening hours to any Eloquent model through a single trait (a shop, a restaurant, an office…). A model can hold more than one schedule (e.g. seasonal), with `latestOpeningHours` / `oldestOpeningHours` helpers.
- **Multiple time ranges per day** — e.g. a lunch break: `09:00-13:00` and `14:00-18:00`.
- **Date exceptions** — override a specific date (holidays, special events). On a date with an exception, only the exception's ranges count for that day.
- **Database-level query scopes** — `openAt`, `closeAt`, `openBetween`, `closedBetween` run as SQL, so you can filter and paginate large tables without hydrating models.
- **Bridge to `spatie/opening-hours`** — turn a stored schedule into a full `OpeningHours` object on demand.
- **Localised weekday labels** — English, Italian and Dutch translations included, backed by a `Day` enum.
- **Swappable models** — point any of the internal models at your own subclass (tenant scoping, extra columns) via config.

## Installation

Install the package via composer:

```bash
composer require datomatic/db-opening-hours
```

Run the migrations (four tables: `opening_hours`, `opening_hours_days`, `opening_hours_exceptions`, `opening_hours_time_ranges`):

```bash
php artisan migrate
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="db-opening-hours-config"
```

Optionally publish the translations:

```bash
php artisan vendor:publish --tag="db-opening-hours-translations"
```

### Config

```php
use Datomatic\DatabaseOpeningHours\Models\Day;
use Datomatic\DatabaseOpeningHours\Models\Exception;
use Datomatic\DatabaseOpeningHours\Models\OpeningHour;
use Datomatic\DatabaseOpeningHours\Models\TimeRange;

return [
    /*
     * Every internal relation resolves its model through this map, so you can
     * point any role at a subclass (for example to add tenant scoping or extra
     * columns) without forking the package. A subclass must extend the package
     * model it replaces and keep its table name.
     */
    'models' => [
        'opening_hour' => OpeningHour::class,
        'day'          => Day::class,
        'exception'    => Exception::class,
        'time_range'   => TimeRange::class,
    ],
];
```

## Data model

| Table | Model | Role |
|-------|-------|------|
| `opening_hours` | `OpeningHour` | One schedule, polymorphically linked to your model (`openable`). |
| `opening_hours_days` | `Day` | A weekday (`monday`…`sunday`) inside a schedule. |
| `opening_hours_exceptions` | `Exception` | A date override inside a schedule. |
| `opening_hours_time_ranges` | `TimeRange` | A `start`–`end` window, belonging (polymorphically) to a `Day` or an `Exception`. |

Weekdays are represented by the `Datomatic\DatabaseOpeningHours\Enums\Day` enum.

## Usage

### Attach opening hours to a model

Add the `HasOpeningHours` trait to any model:

```php
use Datomatic\DatabaseOpeningHours\Traits\HasOpeningHours;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasOpeningHours;
}
```

This gives the model these relations:

```php
$shop->openingHours;        // MorphMany – every schedule attached to the model
$shop->latestOpeningHours;  // MorphOne  – the most recently created schedule
$shop->oldestOpeningHours;  // MorphOne  – the first created schedule
```

### Define a weekly schedule

Create a schedule and set its weekly hours in one call. `syncWeeklySchedule()` replaces the whole schedule: weekdays missing from the payload (or with empty ranges) are removed, so the argument is always the complete new state.

```php
$openingHour = $shop->openingHours()->create(['name' => 'default']);

$openingHour->syncWeeklySchedule([
    'monday'    => [['start' => '09:00', 'end' => '13:00'], ['start' => '14:00', 'end' => '18:00']],
    'tuesday'   => [['start' => '09:00', 'end' => '18:00']],
    'wednesday' => [['start' => '09:00', 'end' => '18:00']],
]);
```

Times accept `9:00`, `09:00` or `09:00:00`; they are normalised before being stored.

Read the schedule back in the same shape:

```php
$openingHour->weeklySchedule();
// [
//     'monday'  => [['start' => '09:00', 'end' => '13:00'], ['start' => '14:00', 'end' => '18:00']],
//     'tuesday' => [['start' => '09:00', 'end' => '18:00']],
//     ...
// ]
```

You also get a relation per weekday (returns a `Day` model or `null`):

```php
$openingHour->monday;   // Day|null
$openingHour->sunday;   // Day|null
$openingHour->days;     // all Day models, ordered Monday → Sunday
```

### Date exceptions

Override a specific date. On that date only the exception's ranges apply — the regular weekday hours are ignored.

```php
$exception = $openingHour->exceptions()->create([
    'date'        => '2024-12-25',
    'description' => 'Christmas – short hours',
]);

$exception->timeRanges()->create(['start' => '10:00', 'end' => '12:00']);
```

### Query the database

The `HasOpeningHours` trait adds SQL query scopes to your model. They accept a date-time string or any `DateTimeInterface`.

```php
// Records open at a given instant (weekday hours, or a matching date exception)
Shop::openAt(now())->get();
Shop::openAt('2024-12-25 11:00')->get();

// Records that have opening hours but are closed at that instant
Shop::closeAt(now())->get();

// Records whose hours fully cover a whole window on a date
Shop::openBetween('2024-01-01', '10:00', '12:00')->get();

// Records that have hours but do not cover that window
Shop::closedBetween('2024-01-01', '10:00', '12:00')->get();
```

Notes:

- `openBetween` uses **containment**: a single range must span the window end to end. A `10:00-11:30` request is *not* satisfied by a `09:00-11:00` range.
- `closeAt` / `closedBetween` deliberately exclude models **without any schedule** — no schedule means "no restriction", not "always closed".

### Get a `spatie/opening-hours` object

When you need the full [`spatie/opening-hours`](https://github.com/spatie/opening-hours) API, build it from the stored schedule. Both the weekly days **and** the date exceptions are fed in, so the object's date-aware methods honour exceptions exactly like the `openAt`/`openBetween` query scopes:

```php
$hours = $openingHour->openingHours(); // Spatie\OpeningHours\OpeningHours

$hours->isOpenAt(new DateTime('2024-01-01 11:00')); // true / false
$hours->nextOpen(new DateTime());
$hours->forDay('monday');                            // weekday ranges
$hours->forDate(new DateTime('2024-12-25'));         // ranges for that date, exceptions applied
```

A date exception replaces the weekday ranges for its date; an exception with no ranges makes that date closed.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Alberto Peripolli](https://github.com/trippo)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
