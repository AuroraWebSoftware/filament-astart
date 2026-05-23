<?php

namespace AuroraWebSoftware\FilamentAstart\Utils;

/**
 * Two-way translator between the Filament Repeater form state and the
 * aauth `rules_json` array format expected by RoleModelAbacRule.
 *
 * Form state shape (Filament Repeater output):
 * [
 *     'logical_operator' => '&&',
 *     'blocks' => [
 *         ['type' => 'condition', 'attribute' => 'status', 'operator' => '=', 'value' => 'active'],
 *         ['type' => 'group', 'group_operator' => '||', 'conditions' => [
 *             ['attribute' => 'region', 'operator' => '=', 'value' => 'EU'],
 *         ]],
 *     ],
 * ]
 *
 * aauth rules_json shape (the actual format consumed by AAuthABACModelScope):
 * [
 *     ['&&' => [
 *         ['=' => ['attribute' => 'status', 'value' => 'active']],
 *         ['||' => [
 *             ['=' => ['attribute' => 'region', 'value' => 'EU']],
 *         ]],
 *     ]],
 * ]
 *
 * NOTE: The aauth README documents the inner shape `['&&' => [...]]` as
 * the top-level rules_json, but `AAuthABACModelScope::apply()` iterates
 * the rules with `foreach ($rules as $rule)` (no key capture), so the
 * top level must be a numerically-indexed list of nodes for iteration
 * to land on a `['&&' => ...]` / `['||' => ...]` / `['=' => ...]` node.
 * Saving the doc-style shape directly leads to "Undefined array key
 * 'attribute'" inside applyConditionalOperator. We always wrap.
 */
class AbacRuleTransformer
{
    public const DEFAULT_LOGICAL_OPERATOR = '&&';

    private const ALLOWED_LOGICAL_OPERATORS = ['&&', '||'];

    /**
     * Convert aauth rules_json → Filament Repeater form state.
     *
     * Tolerates both the wrapped format we save (`[['&&' => [...]]]`)
     * and the legacy/doc-style direct keyed format (`['&&' => [...]]`)
     * so previously-saved data still loads. Returns a valid empty
     * default form state on null / empty / malformed input.
     *
     * @param  array<mixed>|null  $rulesJson
     * @return array{logical_operator: string, blocks: array<int, array<string, mixed>>}
     */
    public static function toFormState(?array $rulesJson): array
    {
        if (empty($rulesJson)) {
            return self::emptyFormState();
        }

        [$topOperator, $children] = self::detectTopLevel($rulesJson);

        if ($topOperator === null || ! is_array($children)) {
            return self::emptyFormState();
        }

        $blocks = [];

        foreach ($children as $node) {
            if (! is_array($node) || empty($node)) {
                continue;
            }

            $key = array_key_first($node);
            $payload = $node[$key];

            if (in_array($key, self::ALLOWED_LOGICAL_OPERATORS, true)) {
                $block = self::groupNodeToBlock($key, $payload);
            } else {
                $block = self::conditionNodeToBlock($key, $payload);
            }

            if ($block !== null) {
                // Plain indexed array — the custom Alpine builder expects
                // `blocks` to be a JS array (Array.isArray) and assigns its
                // own client-side `_uid` per item. Associative/UUID keys
                // would serialise to a JS object and the builder would
                // discard the loaded rules.
                $blocks[] = $block;
            }
        }

        return [
            'logical_operator' => $topOperator,
            'blocks' => $blocks,
        ];
    }

    /**
     * Convert Filament Repeater form state → aauth rules_json.
     *
     * Returns null when the form state is empty or yields no usable
     * blocks; callers should delete the DB row in that case rather than
     * persisting an empty rule (which would break the global scope).
     *
     * The output is wrapped in an outer numeric-indexed array so
     * `AAuthABACModelScope::apply()`'s `foreach ($rules as $rule)`
     * iterates over a single keyed node (`['&&' => [...]]` or
     * `['||' => [...]]`). See class-level NOTE for context.
     *
     * @param  array<string, mixed>|null  $formState
     * @return array<int, array<string, array<int, array<string, mixed>>>>|null
     */
    public static function fromFormState(?array $formState): ?array
    {
        if (empty($formState)) {
            return null;
        }

        $topOperator = $formState['logical_operator'] ?? self::DEFAULT_LOGICAL_OPERATOR;

        if (! in_array($topOperator, self::ALLOWED_LOGICAL_OPERATORS, true)) {
            $topOperator = self::DEFAULT_LOGICAL_OPERATOR;
        }

        $blocks = $formState['blocks'] ?? [];

        if (! is_array($blocks)) {
            return null;
        }

        $children = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = $block['type'] ?? 'condition';

            $node = $type === 'group'
                ? self::blockToGroupNode($block)
                : self::blockToConditionNode($block);

            if ($node !== null) {
                $children[] = $node;
            }
        }

        if (empty($children)) {
            return null;
        }

        return [[$topOperator => $children]];
    }

    /**
     * @return array{logical_operator: string, blocks: array<int, mixed>}
     */
    public static function emptyFormState(): array
    {
        return [
            'logical_operator' => self::DEFAULT_LOGICAL_OPERATOR,
            'blocks' => [],
        ];
    }

    /**
     * Resolve [topOperator, children] from either the wrapped form
     * (`[['&&' => [...]]]`) or the legacy direct-keyed form
     * (`['&&' => [...]]`). Returns [null, null] for unknown shapes.
     *
     * @param  array<mixed>  $rulesJson
     * @return array{0: string|null, 1: array<mixed>|null}
     */
    private static function detectTopLevel(array $rulesJson): array
    {
        $topKey = array_key_first($rulesJson);

        if (is_string($topKey) && in_array($topKey, self::ALLOWED_LOGICAL_OPERATORS, true)) {
            $children = $rulesJson[$topKey];

            return [$topKey, is_array($children) ? $children : null];
        }

        if (is_int($topKey) && count($rulesJson) === 1 && is_array($rulesJson[$topKey])) {
            $node = $rulesJson[$topKey];
            $innerKey = array_key_first($node);

            if (is_string($innerKey) && in_array($innerKey, self::ALLOWED_LOGICAL_OPERATORS, true)) {
                $children = $node[$innerKey];

                return [$innerKey, is_array($children) ? $children : null];
            }
        }

        return [null, null];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function conditionNodeToBlock(string $operator, mixed $payload): ?array
    {
        if (! is_array($payload)) {
            return null;
        }

        $attribute = $payload['attribute'] ?? null;

        if (! is_string($attribute) || $attribute === '') {
            return null;
        }

        return [
            'type' => 'condition',
            'attribute' => $attribute,
            'operator' => $operator,
            'value' => $payload['value'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function groupNodeToBlock(string $logicalOperator, mixed $payload): ?array
    {
        if (! is_array($payload)) {
            return null;
        }

        $conditions = [];

        foreach ($payload as $node) {
            if (! is_array($node) || empty($node)) {
                continue;
            }

            $key = array_key_first($node);

            // Nested groups inside a group are flattened away — the UI is
            // limited to a single nesting level. Conditions are kept.
            if (in_array($key, self::ALLOWED_LOGICAL_OPERATORS, true)) {
                continue;
            }

            $condition = self::conditionNodeToBlock($key, $node[$key]);

            if ($condition !== null) {
                unset($condition['type']);
                // Plain indexed array (see toFormState note) — Alpine
                // expects a JS array and adds its own _uid.
                $conditions[] = $condition;
            }
        }

        if (empty($conditions)) {
            return null;
        }

        return [
            'type' => 'group',
            'group_operator' => $logicalOperator,
            'conditions' => $conditions,
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, array<string, mixed>>|null
     */
    private static function blockToConditionNode(array $block): ?array
    {
        $attribute = $block['attribute'] ?? null;
        $operator = $block['operator'] ?? null;

        if (! is_string($attribute) || $attribute === '') {
            return null;
        }

        if (! is_string($operator) || $operator === '') {
            return null;
        }

        if (! array_key_exists('value', $block)) {
            return null;
        }

        return [
            $operator => [
                'attribute' => $attribute,
                'value' => $block['value'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, array<int, array<string, mixed>>>|null
     */
    private static function blockToGroupNode(array $block): ?array
    {
        // Accept both `group_operator` (current schema) and the legacy
        // `logical_operator` key so rules saved by older builder
        // versions still round-trip without dropping the operator.
        $logicalOperator = $block['group_operator']
            ?? $block['logical_operator']
            ?? self::DEFAULT_LOGICAL_OPERATOR;

        if (! in_array($logicalOperator, self::ALLOWED_LOGICAL_OPERATORS, true)) {
            $logicalOperator = self::DEFAULT_LOGICAL_OPERATOR;
        }

        $conditions = $block['conditions'] ?? [];

        if (! is_array($conditions)) {
            return null;
        }

        $children = [];

        foreach ($conditions as $condition) {
            if (! is_array($condition)) {
                continue;
            }

            $node = self::blockToConditionNode($condition);

            if ($node !== null) {
                $children[] = $node;
            }
        }

        if (empty($children)) {
            return null;
        }

        return [$logicalOperator => $children];
    }
}
