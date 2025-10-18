<?php

namespace AuroraWebSoftware\FilamentAstart\Resources;

use AuroraWebSoftware\AAuth\Facades\AAuth;
use AuroraWebSoftware\AAuth\Models\OrganizationScope;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages\CreateOrganizationScope;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages\EditOrganizationScope;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages\ListOrganizationScopes;
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
    use AStartResourceAccessPolicy;

    protected static ?string $model = OrganizationScope::class;

    protected static null | string | \UnitEnum $navigationGroup = 'AStart';

    protected static null | string | \BackedEnum $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form | \Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label(__('filament-astart::organization-scope.name'))
                    ->required(),

                Placeholder::make('level')
                    ->label(__('filament-astart::organization-scope.level'))
                    ->content(fn (?OrganizationScope $record) => $record?->level)
                    ->visibleOn('edit'),

                Select::make('status')
                    ->label(__('filament-astart::organization-scope.status'))
                    ->required()
                    ->options([
                        'active' => __('filament-astart::organization-scope.status_active'),
                        'passive' => __('filament-astart::organization-scope.status_passive'),
                    ]),

                TextInput::make('level')
                    ->label(__('filament-astart::organization-scope.level'))
                    ->visibleOn('create'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-astart::organization-scope.name'))
                    ->sortable(),

                TextColumn::make('level')
                    ->label(__('filament-astart::organization-scope.level'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('filament-astart::organization-scope.status'))
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
