<?php

namespace AuroraWebSoftware\FilamentAstart;

use AuroraWebSoftware\AAuth\Models\Role;
use AuroraWebSoftware\FilamentAstart\Filament\Pages\RoleSwitch;
use AuroraWebSoftware\FilamentAstart\Http\Middleware\EnsureUserHasRoleSelected;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeV2Resource;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource;
use AuroraWebSoftware\FilamentAstart\Resources\RoleResource;
use AuroraWebSoftware\FilamentAstart\Resources\UserResource;
use AuroraWebSoftware\FilamentAstart\Utils\AAuthUtil;
use Filament\Contracts\Plugin;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

class FilamentAstartPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-astart';
    }

    public function register(Panel $panel): void
    {
        // Pages
        $panel->pages([
            RoleSwitch::class,
        ]);

        // Resources - config'e göre aktif olanları ekle
        $resources = [];

        if (config('filament-astart.resources.user', true)) {
            $resources[] = UserResource::class;
        }
        if (config('filament-astart.resources.role', true)) {
            $resources[] = RoleResource::class;
        }
        if (config('filament-astart.resources.organization_scope', true)) {
            $resources[] = OrganizationScopeResource::class;
        }
        if (config('filament-astart.resources.organization_node', true)) {
            $resources[] = OrganizationNodeResource::class;
        }
        if (config('filament-astart.resources.organization_tree', true)) {
            $resources[] = OrganizationNodeV2Resource::class;
        }

        $panel->resources($resources);

        // Middleware
        $panel->middleware([
            'web',
        ]);

        $panel->authMiddleware([
            EnsureUserHasRoleSelected::class,
        ]);

        // User Menu - Switch Role
        $panel->userMenuItems([
            MenuItem::make()
                ->label(__('filament-astart::filament-astart.role_switch.switch_role'))
                ->url(fn () => route("filament.{$panel->getId()}.pages.role-switch"))
                ->icon('heroicon-o-arrow-path')
                ->visible(fn () => ! AAuthUtil::isSuperAdmin()),
        ]);
    }

    public function boot(Panel $panel): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            function (): string {
                // Super admin kontrolü
                if (AAuthUtil::isSuperAdmin()) {
                    return '<span class="text-sm font-medium text-primary-600 dark:text-primary-400">Super Admin</span>';
                }

                $roleName = Role::find(session('roleId'))?->name;

                if (! $roleName) {
                    return '';
                }

                return '<span class="text-sm font-medium text-gray-700 dark:text-gray-300">' . e($roleName) . '</span>';
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
