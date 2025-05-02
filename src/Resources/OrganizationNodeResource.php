<?php

namespace AuroraWebSoftware\FilamentAstart\Resources;

use AuroraWebSoftware\FilamentAstart\Model\OrganizationNode;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource\Pages\CreateOrganizationNode;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource\Pages\EditOrganizationNode;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource\Pages\ListOrganizationNodes;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationNodeResource extends Resource
{
    protected static ?string $model = OrganizationNode::class;

    protected static ?string $navigationGroup = 'Astart';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (request()->has('parent_id')) {
            $query->where('parent_id', request()->input('parent_id'));
        } else {
            // Only list nodes without parent_id (top-level nodes)
            $query->whereNull('parent_id');
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('organization_scope.name')->label('Organization Scope'),
                TextColumn::make('model_type'),
                TextColumn::make('model_id'),
                TextColumn::make('path')->label('Path'),
                TextColumn::make('parent.name')->label('Parent'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Alt Node')
                    ->color('info')
                    ->icon('heroicon-o-arrow-right')
                    ->url(fn ($record) => "/admin/organization-nodes?parent_id={$record->id}"),
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
            'index' => ListOrganizationNodes::route('/'),
            'create' => CreateOrganizationNode::route('/create'),
            'edit' => EditOrganizationNode::route('/{record}/edit'),
        ];
    }
}
