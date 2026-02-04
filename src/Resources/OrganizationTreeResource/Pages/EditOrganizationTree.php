<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\OrganizationTreeResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\OrganizationTreeResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrganizationTree extends EditRecord
{
    use AStartPageLabels;

    protected static string $resource = OrganizationTreeResource::class;

    protected static ?string $resourceKey = 'organization_tree';

    protected static ?string $pageType = 'edit';

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label(__('filament-astart::filament-astart.resources.organization_node.fields.node_name'))
                ->required()
                ->maxLength(255),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => ! $this->record->children()->exists())
                ->requiresConfirmation()
                ->modalHeading(__('filament-astart::filament-astart.resources.organization_node.actions.delete_node'))
                ->modalDescription(
                    fn () => $this->record->children()->exists()
                    ? __('filament-astart::filament-astart.resources.organization_node.messages.cannot_delete_with_children')
                    : __('filament-astart::filament-astart.resources.organization_node.messages.delete_confirm')
                )
                ->modalSubmitActionLabel(__('filament-astart::filament-astart.resources.organization_node.messages.yes_delete'))
                ->action(function ($record, $action) {
                    try {
                        $recordName = $record->name;
                        $record->delete();

                        Notification::make()
                            ->title(__('filament-astart::filament-astart.resources.organization_node.messages.success'))
                            ->body($recordName . __('filament-astart::filament-astart.resources.organization_node.messages.delete_success'))
                            ->success()
                            ->send();

                        return redirect(static::getResource()::getUrl('index'));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('filament-astart::filament-astart.resources.organization_node.messages.error'))
                            ->body(__('filament-astart::filament-astart.resources.organization_node.messages.delete_error') . $e->getMessage())
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
