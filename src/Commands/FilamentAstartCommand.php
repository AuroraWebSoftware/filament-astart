<?php

namespace AuroraWebSoftware\FilamentAstart\Commands;

use Illuminate\Console\Command;

class FilamentAstartCommand extends Command
{
    public $signature = 'filament-astart:install';

    public $description = 'Filament Astart Plugin Insallation Command';

    public function handle(): void
    {
        try {
            $this->call('migrate');
        } catch (\Throwable $e) {
            $this->error('❌ Migration Exception ' . $e->getMessage());
            return;
        }

        // AAuth
        $this->call('vendor:publish', [
            '--tag' => 'aauth-seeders',
            '--force' => true,
        ]);

        $this->call('db:seed', [
            '--class' => 'SampleDataSeeder',
        ]);

        //Arflow
        $this->call('vendor:publish', [
            '--tag' => 'arflow-config',
            '--force' => true,
        ]);

        //AStart
        $this->call('vendor:publish', [
            '--tag' => 'filament-astart-config',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'filament-astart-lang',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'filament-astart-stubs',
            '--force' => true,
        ]);

        $this->call('migrate');

    }
}
