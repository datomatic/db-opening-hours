<?php

declare(strict_types=1);

use Carbon\Carbon;
use Datomatic\DatabaseOpeningHours\Support\TimeString;

it('normalizes short time strings', function (): void {
    expect(TimeString::normalize('9:00'))->toBe('09:00:00');
});

it('normalizes padded time strings', function (): void {
    expect(TimeString::normalize('09:00'))->toBe('09:00:00');
});

it('keeps full time strings', function (): void {
    expect(TimeString::normalize('09:30:45'))->toBe('09:30:45');
});

it('normalizes DateTimeInterface instances', function (): void {
    expect(TimeString::normalize(new DateTimeImmutable('2024-01-01 14:05:09')))->toBe('14:05:09');
});

it('normalizes Carbon instances', function (): void {
    expect(TimeString::normalize(Carbon::parse('2024-01-01 23:59:00')))->toBe('23:59:00');
});
