<?php

namespace AuroraWebSoftware\FilamentAstart\Commands;

use Illuminate\Console\Command;

class FilamentAstartCommand extends Command
{
    public $signature = 'filament-astart';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
