<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrganizationScopes extends ListRecords
{
    protected static string $resource = OrganizationScopeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('filament-astart::organization-scope.add_scope')),
        ];
    }
}
