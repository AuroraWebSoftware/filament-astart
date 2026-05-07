<?php

namespace AuroraWebSoftware\FilamentAstart;

use AuroraWebSoftware\AAuth\Models\Role;
use AuroraWebSoftware\FilamentAstart\Filament\Pages\RoleSwitch;
use AuroraWebSoftware\FilamentAstart\Http\Middleware\EnsureUserHasRoleSelected;
use AuroraWebSoftware\FilamentAstart\Resources\LogiAuditHistoryResource;
use AuroraWebSoftware\FilamentAstart\Resources\LogiAuditLogResource;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationTreeResource;
use AuroraWebSoftware\FilamentAstart\Resources\RoleResource;
use AuroraWebSoftware\FilamentAstart\Resources\UserResource;
use AuroraWebSoftware\FilamentAstart\Traits\HasLogiAuditIntegration;
use AuroraWebSoftware\FilamentAstart\Utils\AAuthUtil;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile;
use Filament\Contracts\Plugin;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FilamentAstartPlugin implements Plugin
{
    use HasLogiAuditIntegration;

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
            $resources[] = OrganizationTreeResource::class;
        }
        if (config('filament-astart.resources.logiaudit_log.active', true) && static::hasLogiAudit()) {
            $resources[] = LogiAuditLogResource::class;
        }
        if (config('filament-astart.resources.logiaudit_history.active', true) && static::hasLogiAudit()) {
            $resources[] = LogiAuditHistoryResource::class;
        }

        $panel->resources($resources);

        // Middleware
        $panel->middleware([
            'web',
        ]);

        $panel->authMiddleware([
            EnsureUserHasRoleSelected::class,
        ]);

        // Profile Page
        $panel->profile();

        $menuStyle = config('filament-astart.user_menu_style', 'modern');

        if ($menuStyle === 'classic') {
            // Classic mode: only add role switch to Filament's default menu
            $panel->userMenuItems([
                MenuItem::make()
                    ->label(fn () => __('filament-astart::filament-astart.role_switch.switch_role'))
                    ->url(fn () => route("filament.{$panel->getId()}.pages.role-switch"))
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn () => ! AAuthUtil::isSuperAdmin()),
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        // Language Switch Configuration
        $languageSwitchConfig = config('filament-astart.features.language_switch', []);
        if (($languageSwitchConfig['enabled'] ?? false) && class_exists(LanguageSwitch::class)) {
            LanguageSwitch::configureUsing(function (LanguageSwitch $switch) use ($languageSwitchConfig) {
                $switch->locales($languageSwitchConfig['locales'] ?? ['en']);

                if ($languageSwitchConfig['flags'] ?? false) {
                    $switch->flags();
                }

                if ($languageSwitchConfig['circular'] ?? false) {
                    $switch->circular();
                }
            });
        }

        // Panel Switch Configuration
        if (class_exists(PanelSwitch::class)) {
            $panelSwitchConfig = config('filament-astart.features.panel_switch', []);
            $isEnabled = $panelSwitchConfig['enabled'] ?? false;

            PanelSwitch::configureUsing(function (PanelSwitch $panelSwitch) use ($panelSwitchConfig, $isEnabled) {
                // Disabled ise gizle
                if (! $isEnabled) {
                    $panelSwitch->visible(false);

                    return;
                }

                if ($heading = ($panelSwitchConfig['modal_heading'] ?? null)) {
                    $panelSwitch->modalHeading($heading);
                }

                if (isset($panelSwitchConfig['visible'])) {
                    $panelSwitch->visible($panelSwitchConfig['visible']);
                }
            });
        }

        $menuStyle = config('filament-astart.user_menu_style', 'modern');

        if ($menuStyle === 'modern') {
            $this->registerModernUserMenu();
        } else {
            $this->registerClassicUserMenu();
        }
    }

    protected function registerClassicUserMenu(): void
    {
        // Role name next to avatar
        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            function (): string {
                if (AAuthUtil::isSuperAdmin()) {
                    return '<span class="astart-super-admin-badge">Super Admin</span>';
                }

                $roleName = Role::find(session('roleId'))?->name;

                if (! $roleName) {
                    return '';
                }

                return '<span class="astart-role-badge-text">'.e($roleName).'</span>';
            }
        );
    }

    protected function registerModernUserMenu(): void
    {
        // Inject full custom menu via PROFILE_BEFORE, hide default items via CSS
        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_PROFILE_BEFORE,
            function (): string {
                $user = filament()->auth()->user();

                if (! $user) {
                    return '';
                }

                $name = e(filament()->getUserName($user));
                $email = e($user->email);
                $isSuperAdmin = AAuthUtil::isSuperAdmin();
                $panelId = filament()->getCurrentPanel()?->getId() ?? 'admin';
                $avatarEnabled = config('filament-astart.avatar.enabled', false);

                // Avatar or initials
                $avatarHtml = $this->buildAvatarHtml($user, $name, $avatarEnabled);

                // Role badges
                $roleBadges = '';
                if (! $isSuperAdmin) {
                    $roleData = $this->getActiveRoleData();
                    if ($roleData) {
                        $roleBadges = '<span class="astart-modern-role-badge">'.e($roleData['role_name']).'</span>';
                        if ($roleData['node_names']) {
                            $roleBadges .= '<span class="astart-modern-role-badge astart-modern-role-badge--node">'.e($roleData['node_names']).'</span>';
                        }
                    }
                } else {
                    $roleBadges = '<span class="astart-modern-role-badge astart-modern-role-badge--super">Super Admin</span>';
                }

                // URLs
                $profileUrl = e(EditProfile::getUrl());
                $logoutUrl = e(filament()->getLogoutUrl());
                $roleSwitchUrl = $isSuperAdmin ? '' : e(route("filament.{$panelId}.pages.role-switch"));
                $csrfToken = e(csrf_token());

                // Theme switcher
                $hasDarkMode = filament()->hasDarkMode() && ! filament()->hasDarkModeForced();
                $themeSwitcher = '';
                if ($hasDarkMode) {
                    $lightLabel = e(__('filament-astart::filament-astart.user_menu.theme_light'));
                    $darkLabel = e(__('filament-astart::filament-astart.user_menu.theme_dark'));
                    $systemLabel = e(__('filament-astart::filament-astart.user_menu.theme_system'));

                    $themeSwitcher = '<div class="astart-modern-divider"></div>'
                        .'<div class="astart-modern-theme" x-data="{ theme: localStorage.getItem(\'theme\') || \'system\' }">'
                        .'<div class="astart-modern-theme-inner">'
                        .'<button type="button" @click="theme = \'light\'; document.documentElement.classList.remove(\'dark\'); localStorage.setItem(\'theme\', \'light\')" :class="theme === \'light\' ? \'astart-modern-theme-btn astart-modern-theme-btn--active\' : \'astart-modern-theme-btn\'" title="'.$lightLabel.'">'
                        .'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" /></svg>'
                        .'<span>'.$lightLabel.'</span>'
                        .'</button>'
                        .'<button type="button" @click="theme = \'dark\'; document.documentElement.classList.add(\'dark\'); localStorage.setItem(\'theme\', \'dark\')" :class="theme === \'dark\' ? \'astart-modern-theme-btn astart-modern-theme-btn--active\' : \'astart-modern-theme-btn\'" title="'.$darkLabel.'">'
                        .'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" /></svg>'
                        .'<span>'.$darkLabel.'</span>'
                        .'</button>'
                        .'<button type="button" @click="theme = \'system\'; localStorage.removeItem(\'theme\'); document.documentElement.classList.toggle(\'dark\', window.matchMedia(\'(prefers-color-scheme: dark)\').matches)" :class="theme === \'system\' ? \'astart-modern-theme-btn astart-modern-theme-btn--active\' : \'astart-modern-theme-btn\'" title="'.$systemLabel.'">'
                        .'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" /></svg>'
                        .'<span>'.$systemLabel.'</span>'
                        .'</button>'
                        .'</div>'
                        .'</div>';
                }

                // Role switch item
                $roleSwitchItem = '';
                if (! $isSuperAdmin) {
                    $roleSwitchItem = '<a href="'.$roleSwitchUrl.'" class="astart-modern-menu-item">'
                        .'<svg class="astart-modern-menu-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182M15.016 9.348" /></svg>'
                        .'<span>'.e(__('filament-astart::filament-astart.role_switch.switch_role')).'</span>'
                        .'</a>';
                }

                // Filament'in userMenuItems() API'si ile eklenen custom item'ları render et
                $customMenuItemsHtml = $this->buildUserMenuItemsHtml();

                return '<div class="astart-modern-menu" x-init="$nextTick(() => { let el = $el; while (el.nextElementSibling) { el.nextElementSibling.style.display = \'none\'; el = el.nextElementSibling; } let parent = $el.parentElement; if (parent) { Array.from(parent.parentElement.children).forEach(c => { if (c !== parent && !c.contains($el)) c.style.display = \'none\'; }); } })">'
                    // Header - centered avatar
                    .'<div class="astart-modern-header">'
                    .$avatarHtml
                    .'<p class="astart-modern-name">'.$name.'</p>'
                    .'<p class="astart-modern-email" title="'.$email.'">'.$email.'</p>'
                    .'<div class="astart-modern-role">'.$roleBadges.'</div>'
                    .'</div>'
                    // Theme switcher
                    .$themeSwitcher
                    .'<div class="astart-modern-divider"></div>'
                    // Menu items
                    .'<div class="astart-modern-menu-items">'
                    .$roleSwitchItem
                    .$customMenuItemsHtml
                    .'</div>'
                    .'<div class="astart-modern-divider"></div>'
                    // Footer - profile left, logout right
                    .'<div class="astart-modern-footer">'
                    .'<a href="'.$profileUrl.'" class="astart-modern-footer-btn">'
                    .'<svg class="astart-modern-menu-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>'
                    .'<span>'.e(__('filament-astart::filament-astart.user_menu.profile')).'</span>'
                    .'</a>'
                    .'<form method="POST" action="'.$logoutUrl.'">'
                    .'<input type="hidden" name="_token" value="'.$csrfToken.'">'
                    .'<button type="submit" class="astart-modern-footer-btn astart-modern-footer-btn--danger">'
                    .'<svg class="astart-modern-menu-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" /></svg>'
                    .'<span>'.e(__('filament-astart::filament-astart.user_menu.logout')).'</span>'
                    .'</button>'
                    .'</form>'
                    .'</div>'
                    .'</div>';
            }
        );

        // Role name next to avatar in topbar
        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            function (): string {
                if (AAuthUtil::isSuperAdmin()) {
                    return '<span class="astart-super-admin-badge">Super Admin</span>';
                }

                $roleName = Role::find(session('roleId'))?->name;

                if (! $roleName) {
                    return '';
                }

                return '<span class="astart-role-badge-text">'.e($roleName).'</span>';
            }
        );
    }

    protected function buildAvatarHtml(mixed $user, string $escapedName, bool $avatarEnabled): string
    {
        if ($avatarEnabled) {
            // Try reading avatar file directly from storage (works with any disk, no URL issues)
            $avatarPath = $user->avatar_path ?? null;

            if ($avatarPath && Storage::exists($avatarPath)) {
                $mime = Storage::mimeType($avatarPath);
                $content = Storage::get($avatarPath);
                $dataUri = 'data:'.$mime.';base64,'.base64_encode($content);

                return '<img src="'.$dataUri.'" class="astart-modern-avatar-img" alt="'.$escapedName.'" />';
            }

            // Fallback: use Filament avatar URL (ui-avatars.com etc.)
            $avatarUrl = e(filament()->getUserAvatarUrl($user));

            return '<img src="'.$avatarUrl.'" class="astart-modern-avatar-img" alt="'.$escapedName.'" />';
        }

        $avatarUrl = e(filament()->getUserAvatarUrl($user));

        return '<img src="'.$avatarUrl.'" class="astart-modern-avatar-img" alt="'.$escapedName.'" />';
    }

    protected function getInitials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name));

        if (count($words) >= 2) {
            return mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr(end($words), 0, 1));
        }

        return mb_strtoupper(mb_substr($name, 0, 2));
    }

    /**
     * @return array{role_name: string, node_names: string|null}|null
     */
    protected function getActiveRoleData(): ?array
    {
        $roleId = session('roleId');

        if (! $roleId) {
            return null;
        }

        $role = Role::find($roleId);

        if (! $role) {
            return null;
        }

        $user = filament()->auth()->user();
        $nodeNames = DB::table('user_role_organization_node')
            ->leftJoin('organization_nodes', 'organization_nodes.id', '=', 'user_role_organization_node.organization_node_id')
            ->where('user_role_organization_node.user_id', $user?->getAuthIdentifier())
            ->where('user_role_organization_node.role_id', $roleId)
            ->whereNotNull('organization_nodes.name')
            ->pluck('organization_nodes.name')
            ->implode(', ');

        return [
            'role_name' => $role->name,
            'node_names' => $nodeNames ?: null,
        ];
    }

    /**
     * Filament'in userMenuItems() API'si ile eklenen custom item'ları HTML olarak render eder.
     * profile ve logout item'ları modern menüde zaten hard-coded olduğu için filtrelenir.
     */
    protected function buildUserMenuItemsHtml(): string
    {
        $panel = filament()->getCurrentPanel();

        if (! $panel) {
            return '';
        }

        $items = $panel->getUserMenuItems();

        // profile ve logout zaten modern menüde var, onları filtrele
        $customItems = collect($items)
            ->filter(fn (Action $item, string $key) => ! in_array($key, ['profile', 'logout']));

        if ($customItems->isEmpty()) {
            return '';
        }

        $html = '';

        foreach ($customItems as $item) {
            $label = e($item->getLabel() ?? $item->getName());
            $url = $item->getUrl();
            $icon = $item->getIcon();
            $openInNewTab = $item->shouldOpenUrlInNewTab();

            // Icon'u SVG string'e çevir
            $iconHtml = $this->resolveIconHtml($icon);

            $targetAttr = $openInNewTab ? ' target="_blank" rel="noopener noreferrer"' : '';

            if ($url) {
                $html .= '<a href="'.e($url).'" class="astart-modern-menu-item"'.$targetAttr.'>'
                    .$iconHtml
                    .'<span>'.$label.'</span>'
                    .'</a>';
            } else {
                $html .= '<span class="astart-modern-menu-item">'
                    .$iconHtml
                    .'<span>'.$label.'</span>'
                    .'</span>';
            }
        }

        return $html;
    }

    /**
     * Icon değerini (string, BackedEnum, Htmlable) SVG HTML'e çevirir.
     */
    protected function resolveIconHtml(string|\BackedEnum|Htmlable|null $icon): string
    {
        if ($icon === null) {
            return '';
        }

        // Htmlable ise direkt render et
        if ($icon instanceof Htmlable) {
            return '<span class="astart-modern-menu-icon">'.$icon->toHtml().'</span>';
        }

        // BackedEnum (Heroicon enum) ise string'e çevir
        if ($icon instanceof \BackedEnum) {
            $icon = $icon->value;
        }

        // Blade svg() helper ile render et
        try {
            $svg = svg($icon, 'astart-modern-menu-icon')->toHtml();

            return $svg;
        } catch (\Exception) {
            return '';
        }
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
