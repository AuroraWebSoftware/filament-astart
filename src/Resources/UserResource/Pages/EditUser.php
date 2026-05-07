<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\UserResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\UserResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    use AStartPageLabels;

    protected static string $resource = UserResource::class;

    protected static ?string $resourceKey = 'user';

    protected static ?string $pageType = 'edit';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggleActive')
                ->label(fn () => $this->record->is_active
                    ? __('filament-astart::filament-astart.resources.user.actions.deactivate')
                    : __('filament-astart::filament-astart.resources.user.actions.activate'))
                ->icon(fn () => $this->record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn () => $this->record->is_active ? 'danger' : 'success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['is_active' => ! $this->record->is_active]);
                    $this->refreshFormData(['is_active']);
                })
                ->visible(fn () => \Illuminate\Support\Facades\Schema::hasColumn($this->record->getTable(), 'is_active')),
        ];
    }
}
