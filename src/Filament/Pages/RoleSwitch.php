<?php

namespace AuroraWebSoftware\FilamentAstart\Filament\Pages;

use AuroraWebSoftware\AAuth\Models\Role;
use AuroraWebSoftware\FilamentAstart\Utils\AAuthUtil;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class RoleSwitch extends Page
{
    protected static ?string $slug = 'role-switch';

    protected string $view = 'filament-astart::pages.role-switch';

    protected static bool $shouldRegisterNavigation = false;

    protected static string | null | \BackedEnum $navigationIcon = null;

    protected static ?string $title = '';

    public array $roles = [];

    public function getLayout(): string
    {
        return 'filament-astart::layouts.guest';
    }

    public function mount(): void
    {
        // Super admin kontrolü - rol seçmeden direkt dashboard'a yönlendir
        if (AAuthUtil::isSuperAdmin()) {
            $panelId = Filament::getCurrentPanel()?->getId() ?? 'admin';
            $this->redirect(route("filament.{$panelId}.pages.dashboard"));

            return;
        }

        $user = Filament::auth()->user();
        $userId = $user?->getAuthIdentifier();

        $this->roles = DB::table('user_role_organization_node')
            ->where('user_role_organization_node.user_id', $userId)
            ->leftJoin('organization_nodes', 'organization_nodes.id', '=', 'user_role_organization_node.organization_node_id')
            ->leftJoin('roles', 'roles.id', '=', 'user_role_organization_node.role_id')
            ->select(
                'user_role_organization_node.role_id',
                'roles.name as role_name',
                DB::raw("STRING_AGG(organization_nodes.name, ', ') AS node_name")
            )
            ->groupBy('user_role_organization_node.role_id', 'roles.name')
            ->get()
            ->toArray();

        if (count($this->roles) === 1) {
            $this->switchRole($this->roles[0]->role_id);
        }
    }

    public function switchRole(int $roleId)
    {
        $user = Filament::auth()->user();
        $userId = $user?->getAuthIdentifier();

        $role = Role::where('uro.user_id', '=', $userId)
            ->where('roles.id', '=', $roleId)
            ->leftJoin('user_role_organization_node as uro', 'uro.role_id', '=', 'roles.id')
            ->first(['roles.id']);

        if (! $role) {
            abort(403, 'Rol bulunamadı.');
        }

        session(['roleId' => $role->id]);

        $panelId = Filament::getCurrentPanel()?->getId() ?? 'admin';

        return redirect()->route("filament.{$panelId}.pages.dashboard");
    }
}
