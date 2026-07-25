<?php

declare(strict_types=1);

use Datomatic\DatabaseOpeningHours\Enums\Day;

it('exposes the seven weekdays in order', function (): void {
    expect(\array_column(Day::cases(), 'value'))->toBe([
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ]);
});

it('translates the label with the active locale', function (): void {
    app()->setLocale('en');
    expect(Day::MONDAY->label())->toBe('Monday');

    app()->setLocale('it');
    expect(Day::MONDAY->label())->toBe('Lunedì');
});

it('returns the label through getLabel', function (): void {
    app()->setLocale('en');
    expect(Day::SUNDAY->getLabel())->toBe('Sunday');
});
