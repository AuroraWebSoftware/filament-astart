<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource\Pages;


use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrganizationNodes extends ListRecords
{
    protected static string $resource = OrganizationNodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
