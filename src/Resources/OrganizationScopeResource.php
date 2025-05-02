<?php

namespace AuroraWebSoftware\FilamentAstart\Resources;

use App\Filament\Resources\OrganizationScopeResource\Pages;
use App\Filament\Resources\OrganizationScopeResource\RelationManagers;
use AuroraWebSoftware\AAuth\Models\OrganizationScope;
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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required(),
                Placeholder::make('level')->content(fn(?OrganizationScope $record) => $record?->level)->visibleOn('edit'),
                Select::make('status')->required()->options(['active' => 'Active', 'passive' => 'Passive']),
                TextInput::make('level')->visibleOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable(),
                TextColumn::make('level')->sortable(),
                TextColumn::make('status')->sortable(),

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
            'index' => \AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages\ListOrganizationScopes::route('/'),
            'create' => \AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages\CreateOrganizationScope::route('/create'),
            'edit' => \AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages\EditOrganizationScope::route('/{record}/edit'),
        ];
    }
}
