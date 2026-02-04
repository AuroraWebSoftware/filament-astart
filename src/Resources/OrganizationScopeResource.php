<?php

namespace AuroraWebSoftware\FilamentAstart\Resources;

use AuroraWebSoftware\AAuth\Facades\AAuth;
use AuroraWebSoftware\AAuth\Models\OrganizationScope;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages\CreateOrganizationScope;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages\EditOrganizationScope;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages\ListOrganizationScopes;
use AuroraWebSoftware\FilamentAstart\Traits\AStartNavigationGroup;
use AuroraWebSoftware\FilamentAstart\Traits\AStartResourceAccessPolicy;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrganizationScopeResource extends Resource
{
    use AStartNavigationGroup;
    use AStartResourceAccessPolicy;

    protected static ?string $model = OrganizationScope::class;

    protected static ?string $resourceKey = 'organization_scope';

    protected static null | string | \BackedEnum $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('filament-astart::filament-astart.resources.organization_scope.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('filament-astart::filament-astart.resources.organization_scope.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-astart::filament-astart.resources.organization_scope.plural');
    }

    public static function form(Form | \Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label(__('filament-astart::filament-astart.resources.organization_scope.fields.name'))
                    ->required(),

                Placeholder::make('level')
                    ->label(__('filament-astart::filament-astart.resources.organization_scope.fields.level'))
                    ->content(fn (?OrganizationScope $record) => $record?->level)
                    ->visibleOn('edit'),

                Select::make('status')
                    ->label(__('filament-astart::filament-astart.resources.organization_scope.fields.status'))
                    ->required()
                    ->options([
                        'active' => __('filament-astart::filament-astart.resources.organization_scope.fields.status_active'),
                        'passive' => __('filament-astart::filament-astart.resources.organization_scope.fields.status_passive'),
                    ]),

                TextInput::make('level')
                    ->label(__('filament-astart::filament-astart.resources.organization_scope.fields.level'))
                    ->visibleOn('create'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-astart::filament-astart.resources.organization_scope.fields.name'))
                    ->sortable(),

                TextColumn::make('level')
                    ->label(__('filament-astart::filament-astart.resources.organization_scope.fields.level'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('filament-astart::filament-astart.resources.organization_scope.fields.status'))
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                //                    ->authorize(AAuth::can('organization_scope_edit')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganizationScopes::route('/'),
            'create' => CreateOrganizationScope::route('/create'),
            'edit' => EditOrganizationScope::route('/{record}/edit'),
        ];
    }

    //    public static function parseFilamentResourceName(string $class)
    //    {
    //        $classBase = class_basename($class);
    //        $modelName = str_replace('Resource', '', $classBase);
    //        return \Illuminate\Support\Str::snake($modelName);
    //    }
    //
    //    public static function canEdit(Model $record): bool
    //    {
    //        $parsed = self::parseFilamentResourceName(self::class);
    //        if (self::hasPage('edit')){
    //            $page='edit';
    //        }
    //        return AAuth::can($parsed . '_' . $page);
    //    }

}
