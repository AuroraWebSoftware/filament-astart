<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\RoleResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\RoleResource;
use Filament\Pages\Page;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected array $permissionPayload = [];

    public static function isPageBelongsToResource(string $pageClass): bool
    {
        if (! class_exists($pageClass)) {
            return false;
        }

        $reflection = new \ReflectionClass($pageClass);

        if ($reflection->hasProperty('resource')) {
            $property = $reflection->getProperty('resource');
            $property->setAccessible(true);
            $value = $property->getValue();
            if (! empty($value) && is_subclass_of($value, Resource::class)) {
                return true;
            }
        }

        if ($reflection->hasMethod('getResource')) {
            $method = $reflection->getMethod('getResource');

            return $method->getDeclaringClass()->getName() !== Page::class;
        }

        return false;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $assignedCodes = DB::table('role_permission')
            ->where('role_id', $this->record->id)
            ->pluck('permission')
            ->toArray();

        $config = config('astart-auth.permissions');
        $permissions = [];
        $groupToggles = [];
        $allChecked = true;

        foreach ($config as $type => $list) {
            foreach ($list as $group => $actions) {
                $groupAll = true;
                foreach ($actions as $action) {
                    $code = Str::snake($group) . '_' . Str::snake($action);
                    $checked = in_array($code, $assignedCodes);
                    data_set($permissions, "$type.$group.$action", $checked);

                    $groupAll = $groupAll && $checked;
                }
                $groupToggles["select_all_{$type}_{$group}"] = $groupAll;
                $allChecked = $allChecked && $groupAll;
            }
        }

        $data['permissions'] = $permissions;
        $data['select_all_permissions'] = $allChecked;

        foreach ($groupToggles as $key => $state) {
            data_set($data, $key, $state);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        //        dd($data);
        $this->permissionPayload = $this->patchPermissionsForSave($data['permissions'] ?? []);
        unset($data['permissions']);

        return $data;
    }

    protected function afterSave(): void
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
            DB::table('role_permission')
                ->updateOrInsert(['role_id' => $roleId, 'permission' => $code]);
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
