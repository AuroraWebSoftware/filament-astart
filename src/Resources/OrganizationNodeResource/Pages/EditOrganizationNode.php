<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrganizationNode extends EditRecord
{
    protected static string $resource = OrganizationNodeResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label(__('filament-astart::organization-node.node_name'))
                ->required()
                ->maxLength(255),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(function () {
                    $hasChildren = $this->record->children()->exists();

                    return ! $hasChildren;
                })
                ->requiresConfirmation()
                ->modalHeading(__('filament-astart::organization-node.delete_node'))
                ->modalDescription(function () {
                    return $this->record->children()->exists()
                        ? __('filament-astart::organization-node.cannot_delete_with_children')
                        : __('filament-astart::organization-node.delete_confirm');
                })
                ->modalSubmitActionLabel(__('filament-astart::organization-node.yes_delete'))
                ->action(function ($record, $action) {
                    $parentId = $record->parent_id;

                    try {
                        $recordName = $record->name;
                        $record->delete();

                        Notification::make()
                            ->title(__('filament-astart::organization-node.success'))
                            ->body($recordName.__('filament-astart::organization-node.delete_success'))
                            ->success()
                            ->send();
                        if ($parentId) {
                            return redirect("/admin/organization-nodes?parent_id={$parentId}");
                        } else {
                            return redirect('/admin/organization-nodes');
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('filament-astart::organization-node.error'))
                            ->body(__('filament-astart::organization-node.delete_error').$e->getMessage())
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
