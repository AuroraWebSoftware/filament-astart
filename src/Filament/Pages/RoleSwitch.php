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

    public string $userName = '';

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
        $this->userName = $user?->name ?? '';

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

        $previousRoleId = session('roleId');
        session(['roleId' => $role->id]);

        $this->logRoleSwitch($user, $previousRoleId, (int) $role->id);

        $panelId = Filament::getCurrentPanel()?->getId() ?? 'admin';

        return redirect()->route("filament.{$panelId}.pages.dashboard");
    }

    /**
     * Write a semantic `auth.role_switch` entry to LogiAuditLog when
     * audit is enabled and LogiAudit is installed. Silent no-op
     * otherwise. Skipped when the role didn't actually change (initial
     * single-role auto-select).
     */
    private function logRoleSwitch(?object $user, mixed $previousRoleId, int $newRoleId): void
    {
        if (! config('astart-auth.log.enabled', false)) {
            return;
        }

        if (! function_exists('addLog')) {
            return;
        }

        if ($previousRoleId !== null && (int) $previousRoleId === $newRoleId) {
            return;
        }

        try {
            $previousId = $previousRoleId !== null ? (int) $previousRoleId : null;
            $previousName = $previousId !== null ? DB::table('roles')->where('id', $previousId)->value('name') : null;
            $newName = DB::table('roles')->where('id', $newRoleId)->value('name');

            $actor = $this->describeUser($user);
            $from = $previousId !== null
                ? sprintf('%s (#%d)', is_string($previousName) ? $previousName : '—', $previousId)
                : 'yok';
            $to = sprintf('%s (#%d)', is_string($newName) ? $newName : '—', $newRoleId);

            addLog('info', sprintf('%s aktif rolünü %s → %s olarak değiştirdi', $actor, $from, $to), [
                'tag' => 'auth.role_switch',
                'ip_address' => request()?->ip(),
                // Role switching is high-volume and low forensic value;
                // expire after a week so the table doesn't bloat.
                'delete_after_days' => 7,
                'context' => [
                    'previous_role_id' => $previousId,
                    'previous_role_name' => is_string($previousName) ? $previousName : null,
                    'new_role_id' => $newRoleId,
                    'new_role_name' => is_string($newName) ? $newName : null,
                    'user_id' => $user?->getAuthIdentifier(),
                    'user_name' => is_object($user) && isset($user->name) ? $user->name : null,
                    'user_class' => $user !== null ? $user::class : null,
                ],
            ]);
        } catch (\Throwable) {
            // Swallow — audit failure must not break role switching.
        }
    }

    private function describeUser(?object $user): string
    {
        if ($user === null) {
            return 'sistem';
        }

        if (is_object($user) && isset($user->name) && is_string($user->name) && $user->name !== '') {
            return sprintf('%s (#%s)', $user->name, $user->getAuthIdentifier());
        }

        return sprintf('#%s', $user->getAuthIdentifier());
    }
}
