<?php

namespace AuroraWebSoftware\FilamentAstart\Traits;

use AuroraWebSoftware\AAuth\Enums\ABACCondition;
use AuroraWebSoftware\AAuth\Models\RoleModelAbacRule;
use AuroraWebSoftware\AAuth\Utils\ABACUtil;
use AuroraWebSoftware\FilamentAstart\Utils\AAuthUtil;
use AuroraWebSoftware\FilamentAstart\Utils\AbacRuleTransformer;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Shared ABAC rule load/save logic for RoleResource pages
 * (CreateRole + EditRole). Bridges between the Filament form state
 * produced by AbacRuleBuilder and the aauth `role_model_abac_rules` table.
 */
trait HandlesAbacRules
{
    /**
     * Load existing ABAC rules for a role into form state shape,
     * keyed by model_type. Models with no rule yet receive a
     * default empty form state so the UI renders correctly.
     *
     * Auto-heals: if a stored rules_json is in the legacy
     * unwrapped doc-style shape (`['&&' => [...]]`), it is
     * round-tripped through the transformer and re-saved in the
     * wrapped shape that AAuthABACModelScope can iterate without
     * crashing. Idempotent on already-canonical rows.
     *
     * @return array<string, array{logical_operator: string, blocks: array<int, mixed>}>
     */
    protected function loadAbacRules(int $roleId): array
    {
        if (! AAuthUtil::isAbacEnabled()) {
            return [];
        }

        $models = AAuthUtil::getAbacModels();

        if (empty($models)) {
            return [];
        }

        $existing = RoleModelAbacRule::query()
            ->where('role_id', $roleId)
            ->get()
            ->keyBy('model_type');

        $formState = [];

        foreach (array_keys($models) as $modelType) {
            $rule = $existing->get($modelType);
            $rulesJson = $rule?->rules_json;

            $state = AbacRuleTransformer::toFormState(
                is_array($rulesJson) ? $rulesJson : null
            );
            $formState[$modelType] = $state;

            if ($rule === null || ! is_array($rulesJson)) {
                continue;
            }

            $canonical = AbacRuleTransformer::fromFormState($state);

            if ($canonical !== null && $canonical !== $rulesJson) {
                $rule->update(['rules_json' => $canonical]);
            }
        }

        return $formState;
    }

    /**
     * Validate the ABAC rules form payload before persistence.
     *
     * Runs three layers of checks per registered model_type:
     *   1. Per-block structural checks (attribute whitelist, operator,
     *      non-empty value, group non-emptiness).
     *   2. aauth's own ABACUtil::validateAbacRuleArray() against the
     *      transformed rules_json.
     *
     * On any failure a danger Notification is shown and Filament's Halt
     * exception is thrown so the record (Edit) is not saved or (Create)
     * not created.
     *
     * @param  array<string, mixed>  $abacRulesFormState
     */
    protected function validateAbacRulesPayload(array $abacRulesFormState): void
    {
        if (! AAuthUtil::isAbacEnabled()) {
            return;
        }

        $models = AAuthUtil::getAbacModels();

        if (empty($models)) {
            return;
        }

        $operators = array_map(fn (ABACCondition $c): string => $c->value, ABACCondition::cases());
        $errors = [];

        foreach ($models as $modelType => $definition) {
            $modelState = $abacRulesFormState[$modelType] ?? null;

            if (! is_array($modelState)) {
                continue;
            }

            $attributes = is_array($definition['attributes'] ?? null) ? $definition['attributes'] : [];
            $blocks = is_array($modelState['blocks'] ?? null) ? $modelState['blocks'] : [];

            foreach ($blocks as $i => $block) {
                if (! is_array($block)) {
                    continue;
                }

                foreach ($this->validateAbacBlock($block, $attributes, $operators) as $message) {
                    $errors[] = sprintf('[%s #%d] %s', $modelType, $i + 1, $message);
                }
            }

            $rulesJson = AbacRuleTransformer::fromFormState($modelState);

            if ($rulesJson === null) {
                continue;
            }

            try {
                ABACUtil::validateAbacRuleArray($rulesJson);
            } catch (Throwable $e) {
                $errors[] = sprintf('[%s] %s', $modelType, $this->summarizeAbacExceptionMessage($e));
            }
        }

        if (empty($errors)) {
            return;
        }

        Notification::make()
            ->title(__('filament-astart::filament-astart.abac.validation_failed'))
            ->body(implode("\n", $errors))
            ->danger()
            ->persistent()
            ->send();

        throw new Halt;
    }

    /**
     * Persist the ABAC rules form state for a role. Each registered
     * model_type is upserted; rules that resolve to null are deleted.
     * Unregistered model_types in the payload are ignored.
     *
     * @param  array<string, mixed>  $abacRulesFormState
     */
    protected function saveAbacRules(int $roleId, array $abacRulesFormState): void
    {
        if (! AAuthUtil::isAbacEnabled()) {
            return;
        }

        $models = AAuthUtil::getAbacModels();

        if (empty($models)) {
            return;
        }

        DB::transaction(function () use ($roleId, $abacRulesFormState, $models) {
            foreach (array_keys($models) as $modelType) {
                $modelState = $abacRulesFormState[$modelType] ?? null;

                $rulesJson = is_array($modelState)
                    ? AbacRuleTransformer::fromFormState($modelState)
                    : null;

                if ($rulesJson === null) {
                    RoleModelAbacRule::query()
                        ->where('role_id', $roleId)
                        ->where('model_type', $modelType)
                        ->delete();

                    continue;
                }

                RoleModelAbacRule::query()->updateOrCreate(
                    ['role_id' => $roleId, 'model_type' => $modelType],
                    ['rules_json' => $rulesJson]
                );
            }
        });
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  array<string, array<string, mixed>>  $attributes
     * @param  array<int, string>  $operators
     * @return array<int, string>
     */
    private function validateAbacBlock(array $block, array $attributes, array $operators): array
    {
        $type = $block['type'] ?? 'condition';

        if ($type === 'group') {
            $errors = [];
            $logicalOperator = $block['logical_operator'] ?? null;

            if (! in_array($logicalOperator, ['&&', '||'], true)) {
                $errors[] = __('filament-astart::filament-astart.abac.errors.invalid_group_operator');
            }

            $conditions = is_array($block['conditions'] ?? null) ? $block['conditions'] : [];

            if (empty($conditions)) {
                $errors[] = __('filament-astart::filament-astart.abac.errors.empty_group');
            }

            foreach ($conditions as $j => $condition) {
                if (! is_array($condition)) {
                    continue;
                }

                foreach ($this->validateAbacCondition($condition, $attributes, $operators) as $message) {
                    $errors[] = sprintf('cond #%d: %s', $j + 1, $message);
                }
            }

            return $errors;
        }

        return $this->validateAbacCondition($block, $attributes, $operators);
    }

    /**
     * @param  array<string, mixed>  $condition
     * @param  array<string, array<string, mixed>>  $attributes
     * @param  array<int, string>  $operators
     * @return array<int, string>
     */
    private function validateAbacCondition(array $condition, array $attributes, array $operators): array
    {
        $errors = [];
        $whitelist = array_keys($attributes);

        $attribute = $condition['attribute'] ?? null;

        if (! is_string($attribute) || $attribute === '') {
            $errors[] = __('filament-astart::filament-astart.abac.errors.missing_attribute');
        } elseif (! in_array($attribute, $whitelist, true)) {
            $errors[] = __('filament-astart::filament-astart.abac.errors.attribute_not_whitelisted', ['attribute' => $attribute]);
        }

        $operator = $condition['operator'] ?? null;

        if (! is_string($operator) || $operator === '' || ! in_array($operator, $operators, true)) {
            $errors[] = __('filament-astart::filament-astart.abac.errors.invalid_operator');
        }

        if (! array_key_exists('value', $condition)) {
            $errors[] = __('filament-astart::filament-astart.abac.errors.missing_value');

            return $errors;
        }

        $value = $condition['value'];

        if ($value === null || $value === '') {
            $errors[] = __('filament-astart::filament-astart.abac.errors.missing_value');

            return $errors;
        }

        // Tip-aware kontrol: attribute kayıtlı ve `type` tanımlıysa value
        // beklenen tipe uymalı. `like` operatörü SQL'de daima string pattern
        // beklediği için tip kontrolünden muaftır.
        if (
            is_string($attribute)
            && is_string($operator)
            && strtolower($operator) !== 'like'
            && isset($attributes[$attribute]['type'])
            && is_string($attributes[$attribute]['type'])
            && ! $this->valueMatchesType($value, $attributes[$attribute]['type'])
        ) {
            $errors[] = __('filament-astart::filament-astart.abac.errors.value_type_mismatch', [
                'attribute' => $attribute,
                'type' => $attributes[$attribute]['type'],
                'value' => is_scalar($value) ? (string) $value : '—',
            ]);
        }

        return $errors;
    }

    /**
     * Whether the given value satisfies the attribute's declared type.
     * Unknown / unsupported types are treated as a pass so custom
     * registry entries do not break validation.
     */
    private function valueMatchesType(mixed $value, string $expectedType): bool
    {
        return match (strtolower($expectedType)) {
            'numeric', 'integer', 'int', 'float', 'decimal' => is_numeric($value),
            'boolean', 'bool' => $this->isBooleanLike($value),
            'date', 'datetime' => $this->isValidDate($value),
            'string', 'text' => is_scalar($value),
            default => true,
        };
    }

    private function isBooleanLike(mixed $value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        if (is_int($value)) {
            return $value === 0 || $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['0', '1', 'true', 'false', 'yes', 'no'], true);
        }

        return false;
    }

    private function isValidDate(mixed $value): bool
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return strtotime((string) $value) !== false;
    }

    /**
     * Reduce aauth's MessageBag-stringified validation exception
     * down to something printable in a Notification body. Falls back
     * to the raw message when no JSON shape is detected.
     */
    private function summarizeAbacExceptionMessage(Throwable $e): string
    {
        $message = $e->getMessage();
        $decoded = json_decode($message, true);

        if (! is_array($decoded)) {
            return $message;
        }

        $lines = [];

        array_walk_recursive($decoded, function ($item) use (&$lines): void {
            if (is_string($item) && $item !== '') {
                $lines[] = $item;
            }
        });

        return $lines === [] ? $message : implode('; ', $lines);
    }
}
