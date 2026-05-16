<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use AuroraWebSoftware\FilamentAstart\Traits\LogsResourceMutations;
use Filament\Resources\Pages\CreateRecord;

class CreateOrganizationScope extends CreateRecord
{
    use AStartPageLabels;
    use LogsResourceMutations;

    protected static string $resource = OrganizationScopeResource::class;

    protected static ?string $resourceKey = 'organization_scope';

    protected static ?string $pageType = 'create';

    protected function afterCreate(): void
    {
        $this->logCreated($this->record, 'org.scope', 'organizasyon kapsamı');
    }
}
