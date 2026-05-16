<?php

namespace AuroraWebSoftware\FilamentAstart\Traits;

use AuroraWebSoftware\FilamentAstart\Utils\AStartLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared logging helpers for Filament Create / Edit pages that emit
 * `created` / `updated` / `deleted` semantic entries through
 * AStartLogger. Pages set $logTag and $logLabelSingular and call
 * the matching method from their lifecycle hooks.
 *
 * Designed for the simple organisation resources (Scope / Node / Tree)
 * where there's no complex sub-state to track — for richer cases
 * (RoleResource permissions, ABAC) inline custom logging is used.
 */
trait LogsResourceMutations
{
    /** @var array<string, mixed> */
    protected array $loggedPreviousAttributes = [];

    /**
     * Snapshot the record's original attributes before save runs.
     * Call from `mutateFormDataBeforeSave()`.
     */
    protected function snapshotForLog(Model $record): void
    {
        $this->loggedPreviousAttributes = $record->getOriginal();
    }

    protected function logCreated(Model $record, string $tag, string $labelSingular): void
    {
        AStartLogger::log(
            tag: $tag,
            message: sprintf('%s %s oluşturdu', $labelSingular, AStartLogger::describeRecord($record)),
            context: ['action' => 'created'],
            target: $record,
        );
    }

    protected function logUpdated(Model $record, string $tag, string $labelSingular): void
    {
        $changes = $this->summariseChanges($record);

        if ($changes === []) {
            return;
        }

        AStartLogger::log(
            tag: $tag,
            message: sprintf(
                '%s %s güncelledi: %s',
                $labelSingular,
                AStartLogger::describeRecord($record),
                $this->formatChanges($changes),
            ),
            context: ['action' => 'updated', 'changes' => $changes],
            target: $record,
        );
    }

    protected function logDeleted(Model $record, string $tag, string $labelSingular): void
    {
        $label = AStartLogger::describeRecord($record);
        $id = $record->getKey();

        AStartLogger::log(
            tag: $tag,
            message: sprintf('%s %s sildi', $labelSingular, $label),
            context: ['action' => 'deleted', 'id' => $id],
        );
    }

    /**
     * @return array<string, array{from: mixed, to: mixed}>
     */
    protected function summariseChanges(Model $record): array
    {
        $ignored = ['updated_at', 'created_at'];
        $diff = [];

        foreach ($record->getAttributes() as $key => $newValue) {
            if (in_array($key, $ignored, true)) {
                continue;
            }

            $oldValue = $this->loggedPreviousAttributes[$key] ?? null;

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
    protected function formatChanges(array $changes): string
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
