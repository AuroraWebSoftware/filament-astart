<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrganizationNodes extends ListRecords
{
    use AStartPageLabels;

    protected static string $resource = OrganizationNodeResource::class;

    protected static ?string $resourceKey = 'organization_node';

    protected static ?string $pageType = 'list';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('filament-astart::filament-astart.resources.organization_node.actions.add_node'))
                ->url(fn () => static::getResource()::getUrl(
                    'create',
                    request()->filled('parent_id')
                        ? ['parent_id' => request()->integer('parent_id')]
                        : []
                )),
        ];
    }
}
