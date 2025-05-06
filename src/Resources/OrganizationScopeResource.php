<?php

namespace AuroraWebSoftware\FilamentAstart\Resources;

use AuroraWebSoftware\AAuth\Models\OrganizationScope;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages\CreateOrganizationScope;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages\EditOrganizationScope;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages\ListOrganizationScopes;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrganizationScopeResource extends Resource
{
    protected static ?string $model = OrganizationScope::class;

    protected static ?string $navigationGroup = 'AStart';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
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
}
