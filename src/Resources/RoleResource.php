<?php

namespace AuroraWebSoftware\FilamentAstart\Resources;

use AuroraWebSoftware\AAuth\Models\OrganizationScope;
use AuroraWebSoftware\AAuth\Models\Role;
use AuroraWebSoftware\FilamentAstart\Resources\RoleResource\Pages;
use AuroraWebSoftware\FilamentAstart\Traits\AStartNavigationGroup;
use AuroraWebSoftware\FilamentAstart\Traits\AStartResourceAccessPolicy;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleResource extends Resource
{
    use AStartNavigationGroup;
    use AStartResourceAccessPolicy;

    protected static ?string $model = Role::class;

    protected static ?string $resourceKey = 'role';

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-shield-check';

    public static function getNavigationLabel(): string
    {
        return __('filament-astart::filament-astart.resources.role.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('filament-astart::filament-astart.resources.role.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-astart::filament-astart.resources.role.plural');
    }

    public static function form(Form|\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        $permissionConfig = config('astart-auth.permissions');

        $resourceCount = collect($permissionConfig['resource'] ?? [])
            ->filter(fn ($actions) => ! empty($actions))
            ->count();

        $pagesCount = collect($permissionConfig['pages'] ?? [])
            ->filter(fn ($actions) => ! empty($actions))
            ->count();

        $widgetKey = isset($permissionConfig['widget']) ? 'widget' : 'widgets';
        $widgetCount = collect($permissionConfig[$widgetKey] ?? [])
            ->filter(fn ($actions) => ! empty($actions))
            ->count();

        $customCount = collect($permissionConfig['custom_permission'] ?? [])
            ->filter(fn ($actions) => ! empty($actions))
            ->count();

        return $schema
            ->schema([
                Fieldset::make(__('filament-astart::filament-astart.resources.role.plural'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('filament-astart::filament-astart.resources.role.fields.name'))
                            ->required()
                            ->unique(column: 'name', ignoreRecord: true),

                        Toggle::make('status')
                            ->label(__('filament-astart::filament-astart.resources.role.fields.status'))
                            ->onIcon('heroicon-s-check')
                            ->offIcon('heroicon-s-x-mark')
                            ->inline(false)
                            ->default(true)
                            ->formatStateUsing(fn ($state) => $state === 'active' || $state === true)
                            ->dehydrateStateUsing(fn ($state) => $state ? 'active' : 'passive'),
                    ])->columns(2),

                Fieldset::make(__('filament-astart::filament-astart.resources.role.form.organization_settings'))
                    ->schema([
                        Select::make('organization_scope_id')
                            ->label(__('filament-astart::filament-astart.resources.role.fields.organization_scope'))
                            ->placeholder(__('filament-astart::filament-astart.resources.role.form.placeholder_organization_scope_optional'))
                            ->options(
                                fn () => OrganizationScope::query()
                                    ->where('status', 'active')
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->searchable()
                            ->nullable(),
                    ])
                    ->columns(1),

                Grid::make(1)
                    ->schema([
                        Toggle::make('select_all_permissions')
                            ->label(__('filament-astart::filament-astart.resources.role.form.select_all_permissions'))
                            ->live()
                            ->dehydrated(false)
                            ->onIcon('heroicon-s-check')
                            ->offIcon('heroicon-s-x-mark')
                            ->afterStateUpdated(function ($state, Set $set) {
                                $permissionConfig = config('astart-auth.permissions');
                                foreach ($permissionConfig as $type => $groups) {
                                    foreach ($groups as $group => $actions) {
                                        foreach ($actions as $key => $value) {
                                            $actionKey = is_string($value) ? $value : $key;
                                            $set("permissions.$type.$group.$actionKey.enabled", $state);
                                        }
                                        $set("select_all_{$type}_$group", $state);
                                    }
                                }
                            }),
                    ]),

                Tabs::make('Permissions')
                    ->tabs([
                        ...($resourceCount > 0 ? [
                            Tabs\Tab::make(__('filament-astart::filament-astart.resources.role.tabs.resources'))
                                ->badge($resourceCount)
                                ->schema(static::buildPermissionGroups($permissionConfig['resource'] ?? [], 'resource')),
                        ] : []),

                        ...($pagesCount > 0 ? [
                            Tabs\Tab::make(__('filament-astart::filament-astart.resources.role.tabs.pages'))
                                ->badge($pagesCount)
                                ->schema(static::buildPermissionGroups($permissionConfig['pages'] ?? [], 'pages')),
                        ] : []),

                        ...($widgetCount > 0 ? [
                            Tabs\Tab::make(__('filament-astart::filament-astart.resources.role.tabs.widgets'))
                                ->badge($widgetCount)
                                ->schema(static::buildPermissionGroups($permissionConfig[$widgetKey] ?? [], $widgetKey)),
                        ] : []),

                        ...($customCount > 0 ? [
                            Tabs\Tab::make(__('filament-astart::filament-astart.resources.role.tabs.custom'))
                                ->badge($customCount)
                                ->schema(static::buildPermissionGroups($permissionConfig['custom_permission'] ?? [], 'custom_permission')),
                        ] : []),
                    ])
                    ->columnSpan('full'),
            ]);
    }

    protected static function buildPermissionGroups(array $groups, string $type): array
    {
        $fields = [];
        foreach ($groups as $group => $actions) {
            if (empty($actions)) {
                continue;
            }

            $groupDescriptionKey = 'filament-astart::permissions.'.Str::snake($group).'_description';
            $groupDescription = __($groupDescriptionKey);

            // Description yoksa veya key dönüyorsa null yap
            if ($groupDescription === $groupDescriptionKey) {
                $groupDescription = null;
            }

            $section = Section::make(__('filament-astart::permissions.'.Str::snake($group)))
                ->collapsible();

            if ($groupDescription) {
                $section = $section->description($groupDescription);
            }

            $fields[] = $section->schema([
                Toggle::make("select_all_{$type}_$group")
                    ->label(__('filament-astart::filament-astart.resources.role.form.select_all_group'))
                    ->live()
                    ->dehydrated(false)
                    ->afterStateUpdated(function ($state, Set $set) use ($actions, $type, $group) {
                        foreach ($actions as $key => $value) {
                            $actionKey = is_string($value) ? $value : $key;
                            $set("permissions.$type.$group.$actionKey.enabled", $state);
                        }
                    }),
                Grid::make()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema(
                        static::buildPermissionFields($actions, $group, $type)
                    ),
            ]);
        }

        return $fields;
    }

    protected static function buildPermissionFields(array $actions, string $group, string $type): array
    {
        $fields = [];

        foreach ($actions as $key => $value) {
            // Geriye uyumluluk: string ise parametresiz, array ise parametreli
            if (is_string($value)) {
                $actionKey = $value;
                $parameters = [];
            } else {
                $actionKey = $key;
                $parameters = $value['parameters'] ?? [];
            }

            $code = Str::snake($group).'_'.Str::snake($actionKey);
            $label = __('filament-astart::permissions.'.$code);
            $description = __('filament-astart::permissions.'.$code.'_description');

            // Description yoksa veya key dönüyorsa boş bırak
            if ($description === 'filament-astart::permissions.'.$code.'_description') {
                $description = null;
            }

            $checkboxField = Checkbox::make("permissions.$type.$group.$actionKey.enabled")
                ->label($label)
                ->live()
                ->afterStateUpdated(function ($state, Set $set, Get $get) use ($type, $group, $actions) {
                    // Group toggle'ı güncelle
                    $allChecked = true;
                    foreach ($actions as $k => $v) {
                        $aKey = is_string($v) ? $v : $k;
                        if (! $get("permissions.$type.$group.$aKey.enabled")) {
                            $allChecked = false;
                            break;
                        }
                    }
                    $set("select_all_{$type}_{$group}", $allChecked);

                    // Ana toggle'ı güncelle
                    $permissionConfig = config('astart-auth.permissions');
                    $allPermissionsChecked = true;
                    foreach ($permissionConfig as $t => $groups) {
                        foreach ($groups as $g => $acts) {
                            foreach ($acts as $ak => $av) {
                                $aKey = is_string($av) ? $av : $ak;
                                if (! $get("permissions.$t.$g.$aKey.enabled")) {
                                    $allPermissionsChecked = false;
                                    break 3;
                                }
                            }
                        }
                    }
                    $set('select_all_permissions', $allPermissionsChecked);
                });

            // Description varsa hintAction ile tooltip göster
            if ($description) {
                $checkboxField = $checkboxField->hintAction(
                    Action::make('info_'.$code)
                        ->label('')
                        ->icon('heroicon-o-information-circle')
                        ->tooltip($description)
                );
            }

            // Parametreler varsa checkbox ve parametreleri aynı sütunda alt alta göster
            if (! empty($parameters)) {
                $groupSchema = [$checkboxField];
                foreach ($parameters as $paramName => $paramConfig) {
                    $paramField = static::buildParameterField(
                        $type,
                        $group,
                        $actionKey,
                        $paramName,
                        $paramConfig
                    );
                    if ($paramField) {
                        $groupSchema[] = $paramField;
                    }
                }

                $fields[] = Grid::make(1)
                    ->schema($groupSchema);
            } else {
                // Parametresiz checkbox
                $fields[] = $checkboxField;
            }
        }

        return $fields;
    }

    protected static function buildParameterField(
        string $type,
        string $group,
        string $actionKey,
        string $paramName,
        array $paramConfig
    ): mixed {
        $fieldName = "permissions.$type.$group.$actionKey.params.$paramName";
        $paramType = $paramConfig['type'] ?? 'string';
        $default = $paramConfig['default'] ?? null;
        $paramDescription = $paramConfig['description'] ?? $paramName;

        // Translation'dan description almayı dene
        $translationKey = 'filament-astart::permissions.'.Str::snake($group).'_'.Str::snake($actionKey).'_param_'.Str::snake($paramName);
        $translatedDescription = __($translationKey);
        if ($translatedDescription !== $translationKey) {
            $paramDescription = $translatedDescription;
        }

        $checkboxPath = "permissions.$type.$group.$actionKey.enabled";

        $field = match ($paramType) {
            'integer' => TextInput::make($fieldName)
                ->label($paramDescription)
                ->numeric()
                ->default($default)
                ->visible(fn (Get $get): bool => (bool) $get($checkboxPath)),

            'boolean' => Toggle::make($fieldName)
                ->label($paramDescription)
                ->default($default ?? false)
                ->inline(false)
                ->visible(fn (Get $get): bool => (bool) $get($checkboxPath)),

            'array' => Forms\Components\TagsInput::make($fieldName)
                ->label($paramDescription)
                ->default($default ?? [])
                ->visible(fn (Get $get): bool => (bool) $get($checkboxPath)),

            default => TextInput::make($fieldName)
                ->label($paramDescription)
                ->default($default)
                ->visible(fn (Get $get): bool => (bool) $get($checkboxPath)),
        };

        return $field;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-astart::filament-astart.resources.role.fields.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('organization_scope_id')
                    ->label(__('filament-astart::filament-astart.resources.role.fields.organization_scope'))
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '-';
                        }
                        $name = DB::table('organization_scopes')->where('id', $state)->value('name');

                        return $name ?? '-';
                    })
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('filament-astart::filament-astart.resources.role.fields.status'))
                    ->colors([
                        'success' => 'active',
                        'danger' => 'passive',
                    ])
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament-astart::filament-astart.resources.role.fields.status'))
                    ->options([
                        'active' => __('filament-astart::filament-astart.resources.role.fields.status_active'),
                        'passive' => __('filament-astart::filament-astart.resources.role.fields.status_passive'),
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->action(function ($record) {
                        $exists = DB::table('user_role_organization_node')
                            ->where('role_id', $record->id)
                            ->exists();

                        if ($exists) {
                            Notification::make()
                                ->title(__('filament-astart::permissions.cannot_delete_role_assigned_to_user'))
                                ->danger()
                                ->send();

                            return;
                        }

                        DB::table('role_permission')->where('role_id', $record->id)->delete();
                        DB::table('roles')->where('id', $record->id)->delete();

                        Notification::make()
                            ->title(__('filament-astart::permissions.role_deleted_successfully'))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('filament-astart::permissions.delete_role'))
                    ->modalDescription(__('filament-astart::permissions.delete_role_description'))
                    ->modalButton(__('filament-astart::permissions.confirm_delete_button')),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
