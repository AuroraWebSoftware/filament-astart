<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource\Pages;

use AuroraWebSoftware\AAuth\Models\OrganizationScope;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationScopeResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use AuroraWebSoftware\FilamentAstart\Traits\LogsResourceMutations;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;

class EditOrganizationScope extends EditRecord
{
    use AStartPageLabels;
    use LogsResourceMutations;

    protected static string $resource = OrganizationScopeResource::class;

    protected static ?string $resourceKey = 'organization_scope';

    protected static ?string $pageType = 'edit';

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->snapshotForLog($this->record);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->logUpdated($this->record, 'org.scope', 'organizasyon kapsamı');
    }

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
                        $this->logDeleted($record, 'org.scope', 'organizasyon kapsamı');
                        $record->delete();

                        Notification::make()
                            ->title(__('filament-astart::filament-astart.resources.organization_scope.messages.delete_success', ['name' => $name]))
                            ->success()
                            ->send();

                        $action->redirect(
                            OrganizationScopeResource::getUrl('index')
                        );

                    } catch (QueryException $e) {

                        Notification::make()
                            ->title(__('filament-astart::filament-astart.resources.organization_scope.messages.delete_failed'))
                            ->body(
                                str_contains($e->getMessage(), 'roles_organization_scope_id_foreign')
                                    ? __('filament-astart::filament-astart.resources.organization_scope.messages.delete_fk_roles')
                                    : __('filament-astart::messages.delete_fk_generic')
                            )
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                }),

        ];
    }
}
