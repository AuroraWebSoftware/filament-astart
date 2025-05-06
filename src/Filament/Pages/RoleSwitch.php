<?php

namespace AuroraWebSoftware\FilamentAstart\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use AuroraWebSoftware\AAuth\Models\Role;

class RoleSwitch extends Page
{
    protected static ?string $slug = 'role-switch';
    protected static string $view = 'filament-astart::pages.role-switch';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationIcon = null;
    protected static ?string $title = '';

    public array $roles = [];

    public function getLayout(): string
    {
        return 'filament-astart::layouts.guest';
    }

    public static function getRouteName(?string $panel = null): string
    {
        return 'filament.admin.pages.role-switch';
    }

    public function mount(): void
    {
        $this->roles = DB::table('user_role_organization_node')
            ->where('user_role_organization_node.user_id', Auth::id())
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
        $role = Role::where('uro.user_id', '=', Auth::id())
            ->where('roles.id', '=', $roleId)
            ->leftJoin('user_role_organization_node as uro', 'uro.role_id', '=', 'roles.id')
            ->first(['roles.id']);

        if (! $role) {
            abort(403, 'Rol bulunamadı.');
        }

        session(['roleId' => $role->id]);

        return redirect()->route('filament.admin.pages.dashboard');
    }
}
