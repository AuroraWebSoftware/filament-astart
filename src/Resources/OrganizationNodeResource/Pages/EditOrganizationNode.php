<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource\Pages;


use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrganizationNode extends EditRecord
{
    protected static string $resource = OrganizationNodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
