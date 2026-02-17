<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\UserResource\Pages;

use AuroraWebSoftware\AAuth\Enums\RoleType;
use AuroraWebSoftware\AAuth\Models\Role;
use AuroraWebSoftware\FilamentAstart\Resources\UserResource;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assignRole')
                ->label(__('filament-astart::user.assign_role'))
                ->icon('heroicon-o-user-plus')
                ->form(function () {
                    $form = [];

                    $form[] = Select::make('type')
                        ->label(__('filament-astart::user.role_type'))
                        ->options([
                            RoleType::system->value => 'Sistem',
                            RoleType::organization->value => 'Organizasyon',
                        ])
                        ->required()
                        ->live()
                        ->reactive();

                    $form[] = Select::make('role_id')
                        ->label(__('filament-astart::user.select_role'))
                        ->options(function (Get $get) {
                            $type = $get('type');

                            if ($type === RoleType::system->value) {
                                return Role::query()
                                    ->where('type', RoleType::system->value)
                                    ->where('status', 'active')
                                    ->pluck('name', 'id');
                            }

                            if ($type === RoleType::organization->value) {
                                return Role::query()
                                    ->where('type', RoleType::organization->value)
                                    ->where('status', 'active')
                                    ->pluck('name', 'id');
                            }

                            return [];
                        })
                        ->required()
                        ->reactive()
                        ->searchable();

                    //                    ->afterStateUpdated(function (Forms\Components\Select $component, ?string $state) {
                    //                        $amount = $component->getContainer()->getComponent(self::PAYMENT_LINK_AMOUNT);
                    //
                    //                        if ($state != AmountDeterminationMethod::FIXED->value) {
                    //                            $amount->disabled();
                    //                            $amount->state(0);
                    //                        } else {
                    //                            $amount->disabled(false);
                    //                        }
                    //                    })

                    $scopes = DB::table('organization_scopes')
                        ->where('status', 'active')
                        ->orderBy('level')
                        ->get();

                    foreach ($scopes as $index => $scope) {
                        $form[] = Select::make("org_level_{$scope->id}")
                            ->label($scope->name)
                            ->options(function (Get $get) use ($index, $scope, $scopes) {
                                if ($index === 0) {
                                    return DB::table('organization_nodes')
                                        ->where('organization_scope_id', $scope->id)
                                        ->pluck('name', 'id');
                                }

                                $previousScope = $scopes[$index - 1];
                                $parentId = $get("org_level_{$previousScope->id}");
                                if (! $parentId) {
                                    return [];
                                }

                                return DB::table('organization_nodes')
                                    ->where('parent_id', $parentId)
                                    ->where('organization_scope_id', $scope->id)
                                    ->pluck('name', 'id');
                            })
                            ->live()
                            ->reactive()
                            ->searchable()
                            ->hidden(function (Get $get) use ($scope) {
                                if ($get('type') !== RoleType::organization->value) {
                                    return true;
                                }

                                $roleScopeLevel = DB::table('roles')
                                    ->leftJoin('organization_scopes', 'organization_scopes.id', '=', 'roles.organization_scope_id')
                                    ->where('roles.id', $get('role_id'))
                                    ->value('organization_scopes.level');

                                return ! $roleScopeLevel || $roleScopeLevel < $scope->level;
                            });
                    }

                    return $form;
                })
                ->action(function (array $data) {
                    /** @var \App\Models\User $user */
                    $user = $this->record;

                    $selectedOrgNodeId = null;

                    $scopeIds = DB::table('organization_scopes')
                        ->where('status', 'active')
                        ->orderByDesc('level')
                        ->pluck('id');

                    foreach ($scopeIds as $scopeId) {
                        $key = "org_level_{$scopeId}";
                        if (! empty($data[$key])) {
                            $selectedOrgNodeId = $data[$key];

                            break;
                        }
                    }

                    $user->roles()->syncWithoutDetaching([
                        $data['role_id'] => [
                            'organization_node_id' => $selectedOrgNodeId,
                        ],
                    ]);

                    Notification::make()
                        ->title('Rol başarıyla eklendi')
                        ->success()
                        ->send();

                    return redirect()->route('filament.admin.resources.users.view', ['record' => $user->id]);
                }),
        ];
    }
}
