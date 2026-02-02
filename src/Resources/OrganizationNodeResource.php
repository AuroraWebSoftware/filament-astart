<?php

namespace AuroraWebSoftware\FilamentAstart\Resources;

use AuroraWebSoftware\FilamentAstart\Model\OrganizationNode;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource\Pages\CreateOrganizationNode;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource\Pages\EditOrganizationNode;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource\Pages\ListOrganizationNodes;
use AuroraWebSoftware\FilamentAstart\Traits\AStartNavigationGroup;
use AuroraWebSoftware\FilamentAstart\Traits\AStartResourceAccessPolicy;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationNodeResource extends Resource
{
    use AStartNavigationGroup;
    use AStartResourceAccessPolicy;

    protected static ?string $model = OrganizationNode::class;

    protected static ?string $resourceKey = 'organization_node';

    protected static null|string|\BackedEnum $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('filament-astart::filament-astart.resources.organization_node.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('filament-astart::filament-astart.resources.organization_node.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-astart::filament-astart.resources.organization_node.plural');
    }

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Hidden::make('parent_id')
                    ->default(request()->get('parent_id')),

                TextInput::make('name')
                    ->label(__('filament-astart::filament-astart.resources.organization_node.fields.node_name'))
                    ->required()
                    ->maxLength(255),

                Select::make('organization_scope_id')
                    ->label(__('filament-astart::filament-astart.resources.organization_node.fields.organization_scope'))
                    ->options(function (callable $get) {
                        $parentId = $get('parent_id');

                        if ($parentId) {
                            $node = OrganizationNode::find($parentId);

                            return $node?->availableScopes()?->pluck('name', 'id') ?? [];
                        }

                        return [];
                    })
                    ->visible(fn (string $context) => $context === 'create')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('filament-astart::filament-astart.resources.organization_node.fields.node_name')),
                TextColumn::make('organization_scope.name')->label(__('filament-astart::filament-astart.resources.organization_node.fields.organization_scope')),
                TextColumn::make('model_type')->label(__('filament-astart::filament-astart.resources.organization_node.fields.model_type')),
                TextColumn::make('model_id')->label(__('filament-astart::filament-astart.resources.organization_node.fields.model_id')),
                TextColumn::make('path')->label(__('filament-astart::filament-astart.resources.organization_node.fields.path')),
                TextColumn::make('parent.name')->label(__('filament-astart::filament-astart.resources.organization_node.fields.parent')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('Alt Node')
                    ->label(__('filament-astart::filament-astart.resources.organization_node.actions.child_node'))
                    ->color('info')
                    ->icon('heroicon-o-arrow-right')
                    ->url(fn ($record) => "/admin/organization-nodes?parent_id={$record->id}"),
                EditAction::make()
                    ->label(__('filament-astart::filament-astart.resources.organization_node.actions.edit_node')),

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
