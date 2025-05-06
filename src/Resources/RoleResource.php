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
    protected static ?string $navigationLabel = 'Roller';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    /* ─────────────────────────────
     |  Form
     ───────────────────────────── */
    public static function form(Form $form): Form
    {
        $permissionConfig = config('aauth.permissions');

        //  🏷️  Permission gruplarındaki toplam eleman sayıları
        $resourceCount = collect($permissionConfig['resource'] ?? [])
            ->filter(fn($actions) => !empty($actions))
            ->count();

        $pagesCount = collect($permissionConfig['pages'] ?? [])
            ->filter(fn($actions) => !empty($actions))
            ->count();

        $widgetKey = isset($permissionConfig['widget']) ? 'widget' : 'widgets';
        $widgetCount = collect($permissionConfig[$widgetKey] ?? [])
            ->filter(fn($actions) => !empty($actions))
            ->count();

        $customCount = collect($permissionConfig['custom_permission'] ?? [])
            ->filter(fn($actions) => !empty($actions))
            ->count();

        return $form
            ->schema([
                /* ───────── 1. Temel Bilgiler ───────── */
                Fieldset::make('Temel Bilgiler')
                    ->schema([

                        Forms\Components\TextInput::make('name')
                            ->label('Ad')
                            ->required()
                            ->unique(column: 'name', ignoreRecord: true),

                        Toggle::make('status')
                            ->label('Aktif')
                            ->onIcon('heroicon-s-check')
                            ->offIcon('heroicon-s-x-mark')
                            ->onColor('success')
                            ->offColor('danger')
                            ->inline(false)
                            ->default(true)
                            ->formatStateUsing(fn($state) => $state === 'active' || $state === true)
                            ->dehydrateStateUsing(fn($state) => $state ? 'active' : 'passive'),

                    ])->columns(2),

                Fieldset::make('Tür & Organizasyon')
                    ->schema([
                        Select::make('type')
                            ->label('Tür')
                            ->options([
                                'system' => 'Sistem',
                                'organization' => 'Organizasyon',
                            ])
                            ->native(false)
                            ->required()
                            ->reactive(),

                        Select::make('organization_scope_id')
                            ->label('Organizasyon Kapsamı')
                            ->placeholder('Bir kapsam seçin')
                            ->options(
                                fn() => OrganizationScope::query()
                                    ->where('status', 'active')
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->searchable()
                            ->visible(fn(Get $get) => $get('type') === 'organization')
                            ->required(fn(Get $get) => $get('type') === 'organization')
                            ->nullable(),
                    ])
                    ->columns(2),

                Grid::make(1)
                    ->schema([
                        Toggle::make('select_all_permissions')
                            ->label('Tüm İzinleri Seç / Kaldır')
                            ->helperText('Bu role ait bütün izinleri topluca açıp kapatır.')
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
                            Tabs\Tab::make('Resources')
                                ->badge($resourceCount)
                                ->schema(static::buildPermissionGroups($permissionConfig['resource'] ?? [], 'resource'))
                        ] : []),

                        ...($pagesCount > 0 ? [
                            Tabs\Tab::make('Pages')
                                ->badge($pagesCount)
                                ->schema(static::buildPermissionGroups($permissionConfig['pages'] ?? [], 'pages'))
                        ] : []),

                        ...($widgetCount > 0 ? [
                            Tabs\Tab::make('Widgets')
                                ->badge($widgetCount)
                                ->schema(static::buildPermissionGroups($permissionConfig[$widgetKey] ?? [], $widgetKey))
                        ] : []),

                        ...($customCount > 0 ? [
                            Tabs\Tab::make('Custom Permissions')
                                ->badge($customCount)
                                ->schema(static::buildPermissionGroups($permissionConfig['custom_permission'] ?? [], 'custom_permission'))
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

            $fields[] = Section::make($group)
                ->description(Str::headline($group))
                ->collapsible()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Toggle::make("select_all_$groupKey")
                                ->label('Hepsini Seç / Kaldır')
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
                                    collect($actions)->map(fn($action) => Checkbox::make("permissions.$groupKey.$action")
                                        ->label(Str::headline($action))
                                    )->toArray()
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
