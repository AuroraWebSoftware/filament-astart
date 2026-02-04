<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\RoleResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\RoleResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateRole extends CreateRecord
{
    use AStartPageLabels;

    protected static string $resource = RoleResource::class;

    protected static ?string $resourceKey = 'role';

    protected static ?string $pageType = 'create';

    protected array $permissionPayload = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->permissionPayload = $this->processPermissionsForSave($data['permissions'] ?? []);
        unset($data['permissions']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncRolePermissions($this->record->id, $this->permissionPayload);
    }

    private function syncRolePermissions(int $roleId, array $processedPermissions): void
    {
        DB::transaction(function () use ($roleId, $processedPermissions) {
            foreach ($processedPermissions as $permissionData) {
                $this->upsertPermission(
                    $roleId,
                    $permissionData['code'],
                    $permissionData['checked'],
                    $permissionData['parameters']
                );
            }
        });
    }

    private function upsertPermission(int $roleId, string $code, bool $checked, ?array $parameters): void
    {
        if ($checked) {
            DB::table('role_permission')->updateOrInsert(
                [
                    'role_id' => $roleId,
                    'permission' => $code,
                ],
                [
                    'parameters' => $parameters ? json_encode($parameters) : null,
                ]
            );
        } else {
            DB::table('role_permission')
                ->where('role_id', $roleId)
                ->where('permission', $code)
                ->delete();
        }
    }

    private function processPermissionsForSave(array $rawPermissions): array
    {
        $permissions = config('astart-auth.permissions');
        $processed = [];

        foreach ($permissions as $type => $groups) {
            foreach ($groups as $group => $actions) {
                foreach ($actions as $key => $value) {
                    // Geriye uyumluluk: string ise parametresiz, array ise parametreli
                    if (is_string($value)) {
                        $actionKey = $value;
                        $configParameters = [];
                    } else {
                        $actionKey = $key;
                        $configParameters = $value['parameters'] ?? [];
                    }

                    $code = Str::snake($group) . '_' . Str::snake($actionKey);
                    $permissionData = $rawPermissions[$type][$group][$actionKey] ?? [];

                    // Checkbox durumunu al - enabled key'inden
                    $checked = false;
                    if (is_array($permissionData)) {
                        $checked = ! empty($permissionData['enabled']);
                    } elseif (is_bool($permissionData)) {
                        $checked = $permissionData;
                    }

                    // Parametreleri al
                    $parameters = null;
                    if ($checked && ! empty($configParameters)) {
                        $parameters = [];
                        foreach ($configParameters as $paramName => $paramConfig) {
                            $paramValue = $permissionData['params'][$paramName] ?? null;
                            if ($paramValue !== null && $paramValue !== '') {
                                $parameters[$paramName] = $this->castParameterValue($paramValue, $paramConfig['type'] ?? 'string');
                            }
                        }
                        if (empty($parameters)) {
                            $parameters = null;
                        }
                    }

                    $processed[] = [
                        'code' => $code,
                        'checked' => $checked,
                        'parameters' => $parameters,
                    ];
                }
            }
        }

        return $processed;
    }

    private function castParameterValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => (bool) $value,
            'array' => is_array($value) ? $value : [$value],
            default => $value,
        };
    }
}
