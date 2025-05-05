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
        $this->permissionPayload = $data['permissions'] ?? [];
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

            $permissions = config('aauth.permissions');

            foreach ($permissions as $type => $list) {

                if ($type === 'resource') {
                    foreach ($list as $resource => $actions) {
                        foreach ($actions as $action) {
                            $code    = Str::snake($resource).'_'.Str::snake($action);
                            $checked = data_get($rawPermissions, "$type.$resource.$action") === true;
                            $this->upsertPivot($roleId, $code, $checked);
                        }
                    }
                } else {
                    foreach ($list as $item => $maybeActions) {

                        if (! empty($maybeActions)) {
                            foreach ($maybeActions as $action) {
                                $code    = Str::snake($item).'_'.Str::snake($action);
                                $checked = data_get($rawPermissions, "$type.$item.$action") === true;
                                $this->upsertPivot($roleId, $code, $checked);
                            }
                        } else {
                            $code    = Str::snake($item);
                            $checked = data_get($rawPermissions, "$type.$item") === true;
                            $this->upsertPivot($roleId, $code, $checked);
                        }
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
                ->where('role_id',   $roleId)
                ->where('permission', $code)
                ->delete();
        }
    }
}
