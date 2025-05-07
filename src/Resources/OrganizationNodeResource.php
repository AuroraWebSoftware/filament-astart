<?php

namespace AuroraWebSoftware\FilamentAstart\Resources;

use AuroraWebSoftware\FilamentAstart\Model\OrganizationNode;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource\Pages\CreateOrganizationNode;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource\Pages\EditOrganizationNode;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource\Pages\ListOrganizationNodes;
use AuroraWebSoftware\FilamentAstart\Traits\AStartResourceAccessPolicy;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationNodeResource extends Resource
{
    use AStartResourceAccessPolicy;

    protected static ?string $model = OrganizationNode::class;

    protected static ?string $navigationGroup = 'AStart';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        if (request()->route()?->parameter('record')) {
            return parent::getEloquentQuery();
        }

        $query = parent::getEloquentQuery();

        if (request()->filled('parent_id')) {
            $query->where('parent_id', request('parent_id'));
        } else {
            $query->whereNull('parent_id');
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('parent_id')
                    ->default(request()->get('parent_id')),

                TextInput::make('name')
                    ->label(__('filament-astart::organization-node.node_name'))
                    ->required()
                    ->maxLength(255),

                Select::make('organization_scope_id')
                    ->label(__('filament-astart::organization-node.organization_scope'))
                    ->options(function () {
                        $parentId = request()->get('parent_id');

                        if ($parentId) {
                            $node = OrganizationNode::find($parentId);

                            return $node?->availableScopes()?->pluck('name', 'id') ?? [];
                        }

                        return [];
                    })
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('filament-astart::organization-node.node_name')),
                TextColumn::make('organization_scope.name')->label('Organization Scope'),
                TextColumn::make('model_type')->label(__('filament-astart::organization-node.model_type')),
                TextColumn::make('model_id')->label(__('filament-astart::organization-node.model_id')),
                TextColumn::make('path')->label(__('filament-astart::organization-node.path')),
                TextColumn::make('parent.name')->label(__('filament-astart::organization-node.parent')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('Alt Node')
                    ->label(__('filament-astart::organization-node.child_node'))
                    ->color('info')
                    ->icon('heroicon-o-arrow-right')
                    ->url(fn ($record) => "/admin/organization-nodes?parent_id={$record->id}"),
                Tables\Actions\EditAction::make()
                    ->label(__('filament-astart::organization-node.edit_node')),

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
