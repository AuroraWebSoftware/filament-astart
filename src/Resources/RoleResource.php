<?php

namespace AuroraWebSoftware\FilamentAstart\Resources;

use AuroraWebSoftware\AAuth\Models\OrganizationScope;
use AuroraWebSoftware\AAuth\Models\Role;
use AuroraWebSoftware\FilamentAstart\Resources\RoleResource\Pages;
use AuroraWebSoftware\FilamentAstart\Traits\AStartResourceAccessPolicy;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
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
    use AStartResourceAccessPolicy;

    protected static ?string $model = Role::class;

    protected static string | null | \UnitEnum $navigationGroup = 'AStart';

    protected static string | null | \BackedEnum $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('filament-astart::role.model_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-astart::role.model_label');
    }

    public static function form(Form | \Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
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
                Fieldset::make(__('filament-astart::role.resource_label'))
                    ->schema([

                        Forms\Components\TextInput::make('name')
                            ->label(__('filament-astart::role.name'))
                            ->required()
                            ->unique(column: 'name', ignoreRecord: true),

                        Toggle::make('status')
                            ->label(__('filament-astart::role.status'))
                            ->onIcon('heroicon-s-check')
                            ->offIcon('heroicon-s-x-mark')
                            ->inline(false)
                            ->default(true)
                            ->formatStateUsing(fn ($state) => $state === 'active' || $state === true)
                            ->dehydrateStateUsing(fn ($state) => $state ? 'active' : 'passive'),

                    ])->columns(2),

                Fieldset::make(__('filament-astart::role.type_organizations'))
                    ->schema([
                        Select::make('type')
                            ->label(__('filament-astart::role.type'))
                            ->options([
                                'system' => __('filament-astart::role.type_system'),
                                'organization' => __('filament-astart::role.type_organization'),
                            ])
                            ->native(false)
                            ->required()
                            ->reactive(),

                        Select::make('organization_scope_id')
                            ->label(__('filament-astart::role.organization_scope'))
                            ->placeholder(__('filament-astart::role.placeholder_organization_scope'))
                            ->options(
                                fn () => OrganizationScope::query()
                                    ->where('status', 'active')
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->searchable()
                            ->visible(fn (Get $get) => $get('type') === 'organization')
                            ->required(fn (Get $get) => $get('type') === 'organization')
                            ->nullable(),
                    ])
                    ->columns(2),

                Grid::make(1)
                    ->schema([
                        Toggle::make('select_all_permissions')
                            ->label(__('filament-astart::role.select_all_permissions'))
                            ->reactive()
                            ->dehydrated(false)
                            ->onIcon('heroicon-s-check')
                            ->offIcon('heroicon-s-x-mark')
                            ->afterStateUpdated(function ($state, Set $set) {
                                $permissionConfig = config('astart-auth.permissions');
                                foreach ($permissionConfig as $type => $list) {
                                    foreach ($list as $group => $actions) {
                                        foreach ($actions as $action) {
                                            $set("permissions.$type.$group.$action", $state);
                                        }
                                        $set("select_all_{$type}_$group", $state);
                                    }
                                }
                            }),
                    ]),

                Tabs::make('Permissions')
                    ->tabs([
                        ...($resourceCount > 0 ? [
                            Tabs\Tab::make(__('filament-astart::role.permissions_tab_resources'))
                                ->badge($resourceCount)
                                ->schema(static::buildPermissionGroups($permissionConfig['resource'] ?? [], 'resource')),
                        ] : []),

                        ...($pagesCount > 0 ? [
                            Tabs\Tab::make(__('filament-astart::role.permissions_tab_pages'))
                                ->badge($pagesCount)
                                ->schema(static::buildPermissionGroups($permissionConfig['pages'] ?? [], 'pages')),
                        ] : []),

                        ...($widgetCount > 0 ? [
                            Tabs\Tab::make(__('filament-astart::role.permissions_tab_widgets'))
                                ->badge($widgetCount)
                                ->schema(static::buildPermissionGroups($permissionConfig[$widgetKey] ?? [], $widgetKey)),
                        ] : []),

                        ...($customCount > 0 ? [
                            Tabs\Tab::make(__('filament-astart::role.permissions_tab_custom'))
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

            $fields[] = Section::make(__('filament-astart::permissions.' . Str::snake($group)))
                ->collapsible()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Toggle::make("select_all_{$type}_$group")
                                ->label(__('filament-astart::role.select_all_group'))
                                ->reactive()
                                ->dehydrated(false)
                                ->afterStateUpdated(function ($state, Set $set) use ($actions, $type, $group) {
                                    foreach ($actions as $action) {
                                        $set("permissions.$type.$group.$action", $state);
                                    }
                                }),
                            Grid::make()
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ])
                                ->schema(
                                    collect($actions)->map(function ($action) use ($group, $type) {
                                        $code = Str::snake($group) . '_' . Str::snake($action);

                                        return Checkbox::make("permissions.$type.$group.$action")
                                            ->label(__('filament-astart::permissions.' . $code));
                                    })->toArray()
                                ),
                        ]),
                ]);
        }

        return $fields;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('organization_scope_id')
                    ->label('Scope')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '-';
                        }
                        $name = DB::table('organization_scopes')->where('id', $state)->value('name');

                        return $name ?? ' ';
                    })
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary',
                        'warning' => 'organization',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'passive',
                    ])
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tür')
                    ->options([

                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options([

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
