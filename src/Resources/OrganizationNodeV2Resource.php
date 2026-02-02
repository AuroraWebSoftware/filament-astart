<?php

namespace AuroraWebSoftware\FilamentAstart\Resources;

use AuroraWebSoftware\AAuth\Models\OrganizationScope;
use AuroraWebSoftware\FilamentAstart\Model\OrganizationNode;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeV2Resource\Pages;
use AuroraWebSoftware\FilamentAstart\Traits\AStartNavigationGroup;
use AuroraWebSoftware\FilamentAstart\Traits\AStartResourceAccessPolicy;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

class OrganizationNodeV2Resource extends Resource
{
    use AStartNavigationGroup;
    use AStartResourceAccessPolicy;

    protected static ?string $model = OrganizationNode::class;

    protected static ?string $resourceKey = 'organization_tree';

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-share';

    protected static ?string $slug = 'organization-tree';

    public static function getNavigationLabel(): string
    {
        return __('filament-astart::filament-astart.resources.organization_tree.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('filament-astart::filament-astart.resources.organization_tree.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-astart::filament-astart.resources.organization_tree.plural');
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

                        // Root node için tüm scope'ları göster
                        return OrganizationScope::query()
                            ->where('status', 'active')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->visible(fn (string $context) => $context === 'create')
                    ->required(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizationNodesV2::route('/'),
            'create' => Pages\CreateOrganizationNodeV2::route('/create'),
            'edit' => Pages\EditOrganizationNodeV2::route('/{record}/edit'),
        ];
    }
}
