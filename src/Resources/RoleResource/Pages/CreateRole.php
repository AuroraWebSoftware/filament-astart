<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\RoleResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected array $permissionPayload = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->permissionPayload = $this->patchPermissionsForSave($data['permissions'] ?? []);
        unset($data['permissions']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncRolePermissions($this->record->id, $this->permissionPayload);
    }

    private function syncRolePermissions(int $roleId, array $rawPermissions): void
    {
        DB::transaction(function () use ($roleId, $rawPermissions) {
            $permissions = config('astart-auth.permissions');

            foreach ($permissions as $type => $list) {
                foreach ($list as $group => $actions) {
                    foreach ($actions as $action) {
                        $code = Str::snake($group) . '_' . Str::snake($action);
                        $checked = data_get($rawPermissions, "$type.$group.$action") === true;
                        $this->upsertPivot($roleId, $code, $checked);
                    }
                }
            }
        });
    }

    private function upsertPivot(int $roleId, string $code, bool $checked): void
    {
        if ($checked) {
            DB::table('role_permission')->updateOrInsert([
                'role_id' => $roleId,
                'permission' => $code,
            ]);
        } else {
            DB::table('role_permission')
                ->where('role_id', $roleId)
                ->where('permission', $code)
                ->delete();
        }
    }

    private function patchPermissionsForSave(array $rawPermissions): array
    {
        $permissions = config('astart-auth.permissions');
        $patched = $rawPermissions;

        foreach ($permissions as $type => $list) {
            foreach ($list as $group => $actions) {
                foreach ($actions as $action) {
                    if (! isset($patched[$type][$group][$action])) {
                        $patched[$type][$group][$action] = false;
                    }
                }
            }
        }

        return $patched;
    }
}
