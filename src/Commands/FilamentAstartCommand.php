<?php

namespace AuroraWebSoftware\FilamentAstart\Commands;

use Illuminate\Console\Command;

class FilamentAstartCommand extends Command
{
    public $signature = 'filament-astart:install';

    public $description = 'Filament Astart Plugin Insallation Command';

    public function handle(): void
    {
        if ($this->confirm('Would you like to publish all config, language, stub, and seeder files?', true)) {
            $this->info('📦 Publishing all config and migrations before anything else...');
            try {
                $this->call('vendor:publish', ['--tag' => 'arflow-config']);
                $this->call('vendor:publish', ['--tag' => 'filament-astart-config']);
                $this->call('vendor:publish', ['--tag' => 'filament-astart-lang']);
                $this->call('vendor:publish', ['--tag' => 'filament-astart-stubs']);
                $this->call('vendor:publish', ['--tag' => 'filament-astart-seeders']);
            } catch (\Throwable $e) {
                $this->error('❌ An error occurred while publishing files: ' . $e->getMessage());
                return;
            }
        } else {
            $this->info('Publishing config, language, stub, and seeder files was skipped.');
        }

        if ($this->confirm('Would you like to run migrations now?', true)) {
            $this->info('🔁 Running migrations...');
            try {
                $this->call('migrate');
            } catch (\Throwable $e) {
                $this->error('❌ An error occurred while running migrations: ' . $e->getMessage());
                return;
            }
        } else {
            $this->info('Migration step was skipped.');
        }

        if ($this->confirm('Would you like to run the SampleFilamentDataSeeder now?', true)) {
            $this->info('🌱 Running SampleFilamentDataSeeder...');
            try {
                $this->call('db:seed', ['--class' => 'SampleFilamentDataSeeder']);
            } catch (\Throwable $e) {
                $this->error('❌ An error occurred while running the seeder: ' . $e->getMessage());
                return;
            }
        } else {
            $this->info('Seeder step was skipped.');
        }

        $this->info('✅ Filament Astart installation process completed!');
    }


}
