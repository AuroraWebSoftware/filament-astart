<?php

namespace AuroraWebSoftware\FilamentAstart\Resources;

use AuroraWebSoftware\AAuth\Models\OrganizationScope;
use AuroraWebSoftware\AAuth\Models\Role;
use AuroraWebSoftware\FilamentAstart\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class RoleResource extends Resource
{
    /* ─────────────────────────────
     |  Temel Ayarlar
     ───────────────────────────── */
    protected static ?string $model = Role::class;

    protected static ?string $navigationGroup = 'AStart';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('filament-astart::role.model_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-astart::role.model_label');
    }

    public static function form(Form $form): Form
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

        return $form
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
                            ->onColor('success')
                            ->offColor('danger')
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
                            ->helperText(__('filament-astart::role.select_all_permissions_helper'))
                            ->reactive()
                            ->onIcon('heroicon-s-check')
                            ->offIcon('heroicon-s-x-mark')
                            ->onColor('success')
                            ->offColor('gray')
                            ->afterStateUpdated(function ($state, Forms\Set $set) use ($permissionConfig) {
                                foreach (['resource', 'pages', 'widgets', 'custom_permission'] as $type) {
                                    foreach ($permissionConfig[$type] ?? [] as $group => $actions) {
                                        foreach ($actions as $action) {
                                            $set("permissions.$type.$group.$action", $state);
                                        }
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

            $groupKey = "$type.$group";

            $fields[] = Section::make(__('filament-astart::permissions.'.Str::snake($group)))
                ->collapsible()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Toggle::make("select_all_$groupKey")
                                ->label(__('filament-astart::role.select_all_group'))
                                ->reactive()
                                ->onIcon('heroicon-s-check')
                                ->offIcon('heroicon-s-x-mark')
                                ->onColor('success')
                                ->offColor('gray')
                                ->afterStateUpdated(function ($state, Forms\Set $set) use ($actions, $groupKey) {
                                    foreach ($actions as $action) {
                                        $set("permissions.$groupKey.$action", $state);
                                    }
                                }),

                            Grid::make()
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ])
                                ->schema(
                                    collect($actions)->map(function ($action) use ($group, $type) {
                                        $code = Str::snake($group).'_'.Str::snake($action);

                                        return Checkbox::make("permissions.$type.$group.$action")
                                            ->label(__('filament-astart::permissions.'.$code));
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
