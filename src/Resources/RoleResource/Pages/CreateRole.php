<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\RoleResource\Pages;

use AuroraWebSoftware\AAuth\Models\RolePermission;
use AuroraWebSoftware\FilamentAstart\Resources\RoleResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use AuroraWebSoftware\FilamentAstart\Traits\HandlesAbacRules;
use AuroraWebSoftware\FilamentAstart\Utils\AStartLogger;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateRole extends CreateRecord
{
    use AStartPageLabels;
    use HandlesAbacRules;

    protected static string $resource = RoleResource::class;

    protected static ?string $resourceKey = 'role';

    protected static ?string $pageType = 'create';

    protected array $permissionPayload = [];

    protected array $abacRulesPayload = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->permissionPayload = $this->processPermissionsForSave($data['permissions'] ?? []);
        unset($data['permissions']);

        $abacRules = is_array($data['abac_rules'] ?? null) ? $data['abac_rules'] : [];
        $this->validateAbacRulesPayload($abacRules);
        $this->abacRulesPayload = $abacRules;
        unset($data['abac_rules']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncRolePermissions($this->record->id, $this->permissionPayload);
        $this->saveAbacRules((int) $this->record->id, $this->abacRulesPayload);

        AStartLogger::log(
            tag: 'rbac.role',
            message: sprintf('%s adlı rolü oluşturdu', AStartLogger::describeRecord($this->record)),
            context: ['action' => 'created'],
            target: $this->record,
        );

        $this->logPermissionAggregate('created', $this->record->id, [], $this->permissionPayload);
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

    /**
     * Emit a single aggregate `rbac.permissions` log entry summarising
     * the permission delta for this save. `$previous` lists permission
     * codes that were already on the role; `$processed` is the new set
     * from the form (with `checked` boolean).
     *
     * @param  array<int, string>  $previous
     * @param  array<int, array{code: string, checked: bool, parameters: ?array}>  $processed
     */
    private function logPermissionAggregate(string $action, int $roleId, array $previous, array $processed): void
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

        $roleLabel = AStartLogger::describeRecord($this->record);

        AStartLogger::log(
            tag: 'rbac.permissions',
            message: sprintf(
                '%s rolünün yetkilerini %s: eklenen [%s], kaldırılan [%s]',
                $roleLabel,
                $action === 'created' ? 'atadı' : 'güncelledi',
                implode(', ', $added) ?: '—',
                implode(', ', $removed) ?: '—',
            ),
            context: [
                'action' => $action,
                'role_id' => $roleId,
                'added' => $added,
                'removed' => $removed,
            ],
            target: $this->record,
        );
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
