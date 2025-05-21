<?php

namespace AuroraWebSoftware\FilamentAstart;

use AuroraWebSoftware\FilamentAstart\Commands\FilamentAstartCommand;
use AuroraWebSoftware\FilamentAstart\Http\Livewire\StateTransitionListbox;
use AuroraWebSoftware\FilamentAstart\Testing\TestsFilamentAstart;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Facades\Filament;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Gate;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentAstartServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-astart';

    public static string $viewNamespace = 'filament-astart';

    public function configurePackage(Package $package): void
    {

        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands());
//            ->hasInstallCommand(function (InstallCommand $command) {
//                $command
//                    ->publishConfigFile()
//                    ->publishMigrations()
//                    ->askToRunMigrations()
//                    ->askToStarRepoOnGitHub('aurorawebsoftware/filament-astart');
//            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();

        $this->app->scoped('filament-astart', function (): FilamentAstart {
            return new FilamentAstart;
        });

    }

    public function packageBooted(): void
    {
        Livewire::component('arflow-state-transition-listbox', StateTransitionListbox::class);

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['en', 'tr']);
        });

        PanelSwitch::configureUsing(function (PanelSwitch $panelSwitch) {
            $panelSwitch->modalHeading('Available Panels');
        });

        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-astart/{$file->getFilename()}"),
                ], 'filament-astart-stubs');
            }

            $this->publishes([
                __DIR__ . '/../config/astart-auth.php' => config_path('astart-auth.php'),
            ], 'filament-astart-config');

            $this->publishes([
                __DIR__ . '/../resources/lang' => lang_path('vendor/filament-astart'),
            ], 'filament-astart-lang');

            $this->publishes([
                __DIR__ . '/../database/seeders/SampleFilamentDataSeeder.php' => database_path('seeders/SampleFilamentDataSeeder.php'),
            ], 'filament-astart-seeders');

        }

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'filament-astart');

        // Testing
        Testable::mixin(new TestsFilamentAstart);

        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'filament-astart');
    }

    protected function getAssetPackageName(): ?string
    {
        return 'aurorawebsoftware/filament-astart';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // AlpineComponent::make('filament-astart', __DIR__ . '/../resources/dist/components/filament-astart.js'),
            Css::make('filament-astart-styles', __DIR__ . '/../resources/dist/filament-astart.css'),
            Js::make('filament-astart-scripts', __DIR__ . '/../resources/dist/filament-astart.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            FilamentAstartCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_filament-astart_table',
            'create_astart_examples_table',
        ];
    }

    //    public function boot(): void
    //    {
    //        (new PermissionRegistrar())->register(Gate::getFacadeRoot());
    //    }

}
