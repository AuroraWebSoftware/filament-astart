<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages;

use AuroraWebSoftware\AAuth\Models\OrganizationScope;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;

class EditOrganizationScope extends EditRecord
{
    protected static string $resource = OrganizationScopeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deleteScope')
                ->label('Sil')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (OrganizationScope $record, Action $action) {
                    try {
                        $name = $record->name;
                        $record->delete();

                        Notification::make()
                            ->title(__('filament-astart::organization-scope.delete_success', ['name' => $name]))
                            ->success()
                            ->send();

                        $action->redirect(
                            OrganizationScopeResource::getUrl('index')
                        );

                    } catch (QueryException $e) {

                        Notification::make()
                            ->title(__('filament-astart::organization-scope.delete_failed'))
                            ->body(
                                str_contains($e->getMessage(), 'roles_organization_scope_id_foreign')
                                    ? __('filament-astart::organization-scope.delete_fk_roles')
                                    : __('filament-astart::messages.delete_fk_generic')
                            )
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                })

        ];
    }
}
