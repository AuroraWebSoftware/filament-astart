<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrganizationScopes extends ListRecords
{
    use AStartPageLabels;

    protected static string $resource = OrganizationScopeResource::class;

    protected static ?string $resourceKey = 'organization_scope';

    protected static ?string $pageType = 'list';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('filament-astart::filament-astart.resources.organization_scope.actions.add_scope')),
        ];
    }
}
