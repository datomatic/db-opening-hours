<?php

namespace Datomatic\DatabaseOpeningHours\Commands;

use Illuminate\Console\Command;

class DatabaseOpeningHoursCommand extends Command
{
    public $signature = 'db-opening-hours';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
