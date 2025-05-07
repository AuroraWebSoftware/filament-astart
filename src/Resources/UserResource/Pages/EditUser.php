<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\UserResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
