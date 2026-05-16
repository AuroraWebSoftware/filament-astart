<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\RoleResource\Pages;

use AuroraWebSoftware\AAuth\Models\RolePermission;
use AuroraWebSoftware\FilamentAstart\Resources\RoleResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use AuroraWebSoftware\FilamentAstart\Traits\HandlesAbacRules;
use AuroraWebSoftware\FilamentAstart\Utils\AStartLogger;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class EditRole extends EditRecord
{
    use AStartPageLabels;
    use HandlesAbacRules;

    protected static string $resource = RoleResource::class;

    protected static ?string $resourceKey = 'role';

    protected static ?string $pageType = 'edit';

    protected array $permissionPayload = [];

    protected array $abacRulesPayload = [];

    /** @var array<int, string> */
    protected array $previousPermissionCodes = [];

    /** @var array<string, mixed> */
    protected array $previousRoleAttributes = [];

    protected function getHeaderActions(): array
    {
        $assignedUsers = DB::table('user_role_organization_node')
            ->join('users', 'users.id', '=', 'user_role_organization_node.user_id')
            ->leftJoin('organization_nodes', 'organization_nodes.id', '=', 'user_role_organization_node.organization_node_id')
            ->leftJoin('organization_scopes', 'organization_scopes.id', '=', 'organization_nodes.organization_scope_id')
            ->where('user_role_organization_node.role_id', $this->record->id)
            ->select([
                'users.id',
                'users.name',
                'users.email',
                DB::raw("STRING_AGG(DISTINCT organization_nodes.name, ', ') as node_names"),
                DB::raw("STRING_AGG(DISTINCT organization_scopes.name, ', ') as scope_names"),
            ])
            ->groupBy('users.id', 'users.name', 'users.email')
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

        $data['abac_rules'] = $this->loadAbacRules((int) $this->record->id);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->permissionPayload = $this->processPermissionsForSave($data['permissions'] ?? []);
        unset($data['permissions']);

        $abacRules = is_array($data['abac_rules'] ?? null) ? $data['abac_rules'] : [];
        $this->validateAbacRulesPayload($abacRules);
        $this->abacRulesPayload = $abacRules;
        unset($data['abac_rules']);

        // Snapshot what's currently on the role so afterSave can emit
        // human-readable diff logs (changes + permission delta).
        $this->previousRoleAttributes = $this->record->getOriginal();
        $this->previousPermissionCodes = RolePermission::query()
            ->where('role_id', $this->record->id)
            ->pluck('permission')
            ->all();

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncRolePermissions($this->record->id, $this->permissionPayload);
        $this->saveAbacRules((int) $this->record->id, $this->abacRulesPayload);

        $changes = $this->summariseRoleChanges();

        if ($changes !== []) {
            AStartLogger::log(
                tag: 'rbac.role',
                message: sprintf(
                    '%s adlı rolü güncelledi: %s',
                    AStartLogger::describeRecord($this->record),
                    $this->formatChanges($changes),
                ),
                context: [
                    'action' => 'updated',
                    'changes' => $changes,
                ],
                target: $this->record,
            );
        }

        $this->logPermissionAggregate($this->record->id, $this->previousPermissionCodes, $this->permissionPayload);
    }

    /**
     * Compare $this->record's current attributes against the snapshot
     * taken before save, returning only keys whose value actually
     * changed and that aren't pure noise (updated_at).
     *
     * @return array<string, array{from: mixed, to: mixed}>
     */
    private function summariseRoleChanges(): array
    {
        $ignored = ['updated_at', 'created_at'];
        $diff = [];

        foreach ($this->record->getAttributes() as $key => $newValue) {
            if (in_array($key, $ignored, true)) {
                continue;
            }

            $oldValue = $this->previousRoleAttributes[$key] ?? null;

            if ($oldValue === $newValue) {
                continue;
            }

            $diff[$key] = ['from' => $oldValue, 'to' => $newValue];
        }

        return $diff;
    }

    /**
     * @param  array<string, array{from: mixed, to: mixed}>  $changes
     */
    private function formatChanges(array $changes): string
    {
        $parts = [];

        foreach ($changes as $key => $values) {
            $parts[] = sprintf(
                "%s='%s'→'%s'",
                $key,
                is_scalar($values['from']) ? $values['from'] : '—',
                is_scalar($values['to']) ? $values['to'] : '—',
            );
        }

        return implode(', ', $parts);
    }

    /**
     * Emit a single aggregate `rbac.permissions` log entry summarising
     * the permission delta for this save.
     *
     * @param  array<int, string>  $previous
     * @param  array<int, array{code: string, checked: bool, parameters: ?array}>  $processed
     */
    private function logPermissionAggregate(int $roleId, array $previous, array $processed): void
    {
        $newSet = collect($processed)
            ->filter(fn (array $p): bool => $p['checked'])
            ->pluck('code')
            ->all();

        $added = array_values(array_diff($newSet, $previous));
        $removed = array_values(array_diff($previous, $newSet));

        if ($added === [] && $removed === []) {
            return;
        }

        AStartLogger::log(
            tag: 'rbac.permissions',
            message: sprintf(
                '%s rolünün yetkilerini güncelledi: eklenen [%s], kaldırılan [%s]',
                AStartLogger::describeRecord($this->record),
                implode(', ', $added) ?: '—',
                implode(', ', $removed) ?: '—',
            ),
            context: [
                'action' => 'updated',
                'role_id' => $roleId,
                'added' => $added,
                'removed' => $removed,
            ],
            target: $this->record,
        );
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
        // Eloquent route keeps RolePermission's audit observer in the loop.
        // For deletes we fetch first and call $row->delete() so the
        // `deleted` event fires (mass deletes via builder do not).
        if ($checked) {
            // `parameters` is array-cast on RolePermission, so pass the
            // raw array — Eloquent handles JSON encoding. Manual
            // json_encode() double-encodes and breaks aauth's
            // PermissionAddedEvent which expects ?array.
            RolePermission::query()->updateOrCreate(
                [
                    'role_id' => $roleId,
                    'permission' => $code,
                ],
                [
                    'parameters' => $parameters,
                ]
            );

            return;
        }

        RolePermission::query()
            ->where('role_id', $roleId)
            ->where('permission', $code)
            ->get()
            ->each(fn (RolePermission $row) => $row->delete());
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
