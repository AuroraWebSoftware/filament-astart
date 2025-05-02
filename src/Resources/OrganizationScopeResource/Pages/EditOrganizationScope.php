<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrganizationScope extends EditRecord
{
    protected static string $resource = OrganizationScopeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
