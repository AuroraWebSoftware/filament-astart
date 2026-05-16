<?php

namespace AuroraWebSoftware\FilamentAstart\Commands;

use AuroraWebSoftware\AAuth\Models\RoleModelAbacRule;
use AuroraWebSoftware\FilamentAstart\Utils\AbacRuleTransformer;
use Illuminate\Console\Command;

/**
 * One-shot repair command for ABAC rules saved in the legacy
 * unwrapped doc-style shape (`['&&' => [...]]`). Re-saves them in
 * the wrapped shape that AAuthABACModelScope can iterate without
 * crashing. Idempotent on already-canonical rows.
 */
class NormalizeAbacRulesCommand extends Command
{
    public $signature = 'filament-astart:abac:normalize {--dry-run : Show what would change without writing}';

    public $description = 'Normalize legacy ABAC rules_json rows to the wrapped shape expected by AAuthABACModelScope';

    public function handle(): int
    {
        $rules = RoleModelAbacRule::query()->get();

        if ($rules->isEmpty()) {
            $this->info('No ABAC rules found. Nothing to normalize.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $skipped = 0;
        $invalid = 0;

        foreach ($rules as $rule) {
            $current = $rule->rules_json;

            if (! is_array($current)) {
                $invalid++;

                $this->line(sprintf(
                    '  <fg=red>SKIP</> role=%d type=%s — non-array rules_json',
                    $rule->role_id,
                    $rule->model_type
                ));

                continue;
            }

            $formState = AbacRuleTransformer::toFormState($current);
            $canonical = AbacRuleTransformer::fromFormState($formState);

            if ($canonical === null) {
                $invalid++;

                $this->line(sprintf(
                    '  <fg=yellow>SKIP</> role=%d type=%s — produced empty form state (deleting recommended)',
                    $rule->role_id,
                    $rule->model_type
                ));

                continue;
            }

            if ($canonical === $current) {
                $skipped++;

                continue;
            }

            $this->line(sprintf(
                '  <fg=cyan>FIX</>  role=%d type=%s',
                $rule->role_id,
                $rule->model_type
            ));

            if (! $dryRun) {
                $rule->update(['rules_json' => $canonical]);
            }

            $updated++;
        }

        $this->newLine();
        $this->info(sprintf(
            '%s — updated: %d · already canonical: %d · invalid/skipped: %d',
            $dryRun ? 'Dry run finished (no DB writes)' : 'Done',
            $updated,
            $skipped,
            $invalid
        ));

        return self::SUCCESS;
    }
}
