<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use Filament\Resources\Pages\CreateRecord;

class CreateOrganizationScope extends CreateRecord
{
    use AStartPageLabels;

    protected static string $resource = OrganizationScopeResource::class;

    protected static ?string $resourceKey = 'organization_scope';

    protected static ?string $pageType = 'create';
}
