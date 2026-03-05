<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\RoleResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\RoleResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class EditRole extends EditRecord
{
    use AStartPageLabels;

    protected static string $resource = RoleResource::class;

    protected static ?string $resourceKey = 'role';

    protected static ?string $pageType = 'edit';

    protected array $permissionPayload = [];

    protected function getHeaderActions(): array
    {
        $assignedUsers = DB::table('user_role_organization_node')
            ->join('users', 'users.id', '=', 'user_role_organization_node.user_id')
            ->leftJoin('organization_nodes', 'organization_nodes.id', '=', 'user_role_organization_node.organization_node_id')
            ->where('user_role_organization_node.role_id', $this->record->id)
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'organization_nodes.name as organization_node_name',
            ])
            ->distinct()
            ->get();

        return [
            Action::make('assignedUsers')
                ->label(__('filament-astart::filament-astart.resources.role.assigned_users.button'))
                ->icon('heroicon-o-users')
                ->color('gray')
                ->badge($assignedUsers->count())
                ->modalHeading(__('filament-astart::filament-astart.resources.role.assigned_users.heading'))
                ->modalDescription(__('filament-astart::filament-astart.resources.role.assigned_users.description', ['role' => $this->record->name]))
                ->modalContent(new HtmlString(
                    view('filament-astart::modals.assigned-users', [
                        'users' => $assignedUsers,
                    ])->render()
                ))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('filament-astart::filament-astart.resources.role.assigned_users.close')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $assignedPermissions = DB::table('role_permission')
            ->where('role_id', $this->record->id)
            ->get()
            ->keyBy('permission');

        $config = config('astart-auth.permissions');
        $permissions = [];
        $groupToggles = [];
        $allChecked = true;

        foreach ($config as $type => $groups) {
            foreach ($groups as $group => $actions) {
                $groupAll = true;

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
                    $dbPermission = $assignedPermissions->get($code);
                    $checked = $dbPermission !== null;

                    // Permission checkbox durumu - enabled key'inde tut
                    data_set($permissions, "$type.$group.$actionKey.enabled", $checked);

                    // Parametreleri yükle
                    if (! empty($configParameters)) {
                        $savedParameters = [];
                        if ($dbPermission?->parameters) {
                            $savedParameters = json_decode($dbPermission->parameters, true) ?? [];
                        }
                        foreach ($configParameters as $paramName => $paramConfig) {
                            $paramValue = $savedParameters[$paramName] ?? $paramConfig['default'] ?? null;
                            data_set($permissions, "$type.$group.$actionKey.params.$paramName", $paramValue);
                        }
                    }

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
        $this->permissionPayload = $this->processPermissionsForSave($data['permissions'] ?? []);
        unset($data['permissions']);

        return $data;
    }

    protected function afterSave(): void
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
