<?php

declare(strict_types=1);

namespace Datomatic\DatabaseOpeningHours\Enums;

enum Day: string
{
    case MONDAY = 'monday';
    case TUESDAY = 'tuesday';
    case WEDNESDAY = 'wednesday';
    case THURSDAY = 'thursday';
    case FRIDAY = 'friday';
    case SATURDAY = 'saturday';
    case SUNDAY = 'sunday';

    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function label(): string
    {
        return __('db-opening-hours::days.'.$this->value);
    }
}
