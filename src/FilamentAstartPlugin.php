<?php

namespace AuroraWebSoftware\FilamentAstart;

use AuroraWebSoftware\FilamentAstart\Pages\Settings;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource;
use AuroraWebSoftware\FilamentAstart\Resources\RoleResource;
use AuroraWebSoftware\FilamentAstart\Resources\UserResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentAstartPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-astart';
    }

    public function register(Panel $panel): void
    {
        $panel->pages(
            [
                Settings::class,
            ]);
        $panel->resources(
            [
                OrganizationScopeResource::class,
                OrganizationNodeResource::class,
                UserResource::class,
                RoleResource::class,
            ]
        );

    }

    public function boot(Panel $panel): void
    {

    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
