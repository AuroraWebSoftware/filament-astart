<?php

namespace AuroraWebSoftware\FilamentAstart;

use AuroraWebSoftware\AAuth\Models\Role;
use AuroraWebSoftware\FilamentAstart\Filament\Pages\RoleSwitch;
use AuroraWebSoftware\FilamentAstart\Http\Middleware\EnsureUserHasRoleSelected;
use AuroraWebSoftware\FilamentAstart\Pages\Demo;
use AuroraWebSoftware\FilamentAstart\Pages\Settings;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource;
use AuroraWebSoftware\FilamentAstart\Resources\RoleResource;
use AuroraWebSoftware\FilamentAstart\Resources\UserResource;
use Filament\Contracts\Plugin;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Hasnayeen\Themes\ThemesPlugin;

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
                RoleSwitch::class,
                Demo::class,
            ]
        );
        $panel->resources(
            [
                OrganizationScopeResource::class,
                OrganizationNodeResource::class,
                UserResource::class,
                RoleResource::class,
            ]
        );
        $panel->middleware([
            EnsureUserHasRoleSelected::class,
        ]);

        $panel->userMenuItems([
            MenuItem::make()
                ->label('Rol Değiştir')
                ->url('/admin/role-switch')
                ->icon('heroicon-o-arrow-path'),
        ]);

        $panel->plugin(ThemesPlugin::make())->middleware([

            \Hasnayeen\Themes\Http\Middleware\SetTheme::class,
        ])
            // or in `tenantMiddleware` if you're using multi-tenancy
            ->tenantMiddleware([

                \Hasnayeen\Themes\Http\Middleware\SetTheme::class,
            ]);

    }

    public function boot(Panel $panel): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            function (): string {
                $roleName = Role::find(session('roleId'))?->name;

                if (! $roleName) {
                    return '';
                }

                return <<<HTML
                <div class="mr-4 text-sm text-gray-700 dark:text-white font-medium hidden sm:block">{$roleName}</span>
                </div>
            HTML;
            }
        );

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
