<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Datomatic\DatabaseOpeningHours\Traits\HasOpeningHours;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasOpeningHours;
}
