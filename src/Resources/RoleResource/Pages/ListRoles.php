<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\RoleResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\RoleResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    use AStartPageLabels;

    protected static string $resource = RoleResource::class;

    protected static ?string $resourceKey = 'role';

    protected static ?string $pageType = 'list';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
