<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource;
use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrganizationNodes extends ListRecords
{
    protected static string $resource = OrganizationNodeResource::class;


    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('filament-astart::organization-node.add_node'))
                ->url(fn() => static::getResource()::getUrl(
                    'create',
                    request()->filled('parent_id')
                        ? ['parent_id' => request()->integer('parent_id')]
                        : []
                )),
        ];
    }
}
