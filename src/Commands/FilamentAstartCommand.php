<?php

namespace AuroraWebSoftware\FilamentAstart\Commands;

use Illuminate\Console\Command;

class FilamentAstartCommand extends Command
{
    public $signature = 'filament-astart:install';

    public $description = 'Filament Astart Plugin Insallation Command';

    public function handle(): void
    {
        $this->warn('⚠️  This installation will overwrite existing config, language, and stub files using --force.');

        if (! $this->confirm('Do you want to continue?', false)) {
            $this->info('❌ Installation cancelled by user.');

            return;
        }

        $this->info('📦 Publishing all config and migrations before anything else...');

        // Publish configs & resources
        $this->call('vendor:publish', ['--tag' => 'arflow-config', '--force' => true]);
        $this->call('vendor:publish', ['--tag' => 'filament-astart-config', '--force' => true]);
        $this->call('vendor:publish', ['--tag' => 'filament-astart-lang', '--force' => true]);
        $this->call('vendor:publish', ['--tag' => 'filament-astart-stubs', '--force' => true]);
        $this->call('vendor:publish', ['--tag' => 'filament-astart-seeders', '--force' => true]);

        $this->info('🔁 Running migrations...');
        $this->call('migrate');

        $this->info('🌱 Running SampleFilamentDataSeeder...');
        $this->call('db:seed', ['--class' => 'SampleFilamentDataSeeder']);

        $this->info('✅ Filament Astart installation completed successfully!');
    }
}
