<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\UserResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\UserResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    use AStartPageLabels;

    protected static string $resource = UserResource::class;

    protected static ?string $resourceKey = 'user';

    protected static ?string $pageType = 'list';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
