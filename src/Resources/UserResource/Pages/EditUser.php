<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\UserResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\UserResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use AuroraWebSoftware\FilamentAstart\Utils\AStartLogger;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Schema;

class EditUser extends EditRecord
{
    use AStartPageLabels;

    protected static string $resource = UserResource::class;

    protected static ?string $resourceKey = 'user';

    protected static ?string $pageType = 'edit';

    /** @var array<string, mixed> */
    protected array $previousUserAttributes = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->previousUserAttributes = $this->record->getOriginal();

        return $data;
    }

    protected function afterSave(): void
    {
        $changes = $this->summariseUserChanges();

        if ($changes === []) {
            return;
        }

        AStartLogger::log(
            tag: 'user.lifecycle',
            message: sprintf(
                '%s adlı kullanıcıyı güncelledi: %s',
                AStartLogger::describeRecord($this->record),
                $this->formatChanges($changes),
            ),
            context: [
                'action' => 'updated',
                'target_user_id' => $this->record->getKey(),
                'target_user_name' => $this->record->name ?? null,
                'changes' => $changes,
            ],
            target: $this->record,
        );
    }

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
                    $wasActive = (bool) $this->record->is_active;
                    $this->record->update(['is_active' => ! $wasActive]);
                    $this->refreshFormData(['is_active']);

                    AStartLogger::log(
                        tag: 'user.status',
                        message: sprintf(
                            '%s adlı kullanıcıyı %s',
                            AStartLogger::describeRecord($this->record),
                            $wasActive ? 'pasifleştirdi' : 'aktifleştirdi',
                        ),
                        context: [
                            'action' => $wasActive ? 'deactivated' : 'activated',
                            'previous_is_active' => $wasActive,
                            'new_is_active' => ! $wasActive,
                            'target_user_id' => $this->record->getKey(),
                            'target_user_name' => $this->record->name ?? null,
                        ],
                        target: $this->record,
                    );
                })
                ->visible(fn () => Schema::hasColumn($this->record->getTable(), 'is_active')),
        ];
    }

    /**
     * @return array<string, array{from: mixed, to: mixed}>
     */
    private function summariseUserChanges(): array
    {
        // Exclude noise + secrets even though the trait-based history
        // path is no longer registered, callers might wire LogiAuditTrait
        // independently; we still don't want the *log* feed to expose
        // password hashes or tokens.
        $ignored = ['updated_at', 'created_at', 'password', 'remember_token'];
        $diff = [];

        foreach ($this->record->getAttributes() as $key => $newValue) {
            if (in_array($key, $ignored, true)) {
                continue;
            }

            $oldValue = $this->previousUserAttributes[$key] ?? null;

            if ($oldValue === $newValue) {
                continue;
            }

            $diff[$key] = ['from' => $oldValue, 'to' => $newValue];
        }

        return $diff;
    }

    /**
     * @param  array<string, array{from: mixed, to: mixed}>  $changes
     */
    private function formatChanges(array $changes): string
    {
        $parts = [];

        foreach ($changes as $key => $values) {
            $parts[] = sprintf(
                "%s='%s'→'%s'",
                $key,
                is_scalar($values['from']) ? $values['from'] : '—',
                is_scalar($values['to']) ? $values['to'] : '—',
            );
        }

        return implode(', ', $parts);
    }
}
