<?php

namespace AuroraWebSoftware\FilamentAstart\Forms\Components;

use AuroraWebSoftware\AAuth\Enums\ABACCondition;
use AuroraWebSoftware\FilamentAstart\Utils\AAuthUtil;
use Filament\Forms\Components\Field;

/**
 * Custom ABAC rule builder field.
 *
 * Replaces the Filament Repeater-based builder. The whole rule tree is
 * managed client-side by an Alpine component (add / remove / reorder
 * happen in the browser with no Livewire round-trips), entangled to
 * this field's state. This sidesteps Filament Repeater's `wire:key`
 * collisions and nested-repeater reactivity bugs that broke add/delete
 * in production.
 *
 * The state shape is unchanged, so AbacRuleTransformer and
 * HandlesAbacRules validation keep working as-is:
 *
 * [
 *     'logical_operator' => '&&',
 *     'blocks' => [
 *         ['type' => 'condition', 'attribute' => 'status', 'operator' => '=', 'value' => 'active'],
 *         ['type' => 'group', 'group_operator' => '||', 'conditions' => [
 *             ['attribute' => 'region', 'operator' => '=', 'value' => 'EU'],
 *         ]],
 *     ],
 * ]
 */
class AbacRuleField extends Field
{
    protected string $view = 'filament-astart::forms.components.abac-rule-builder';

    protected string $abacModelType = '';

    public static function forModel(string $modelType, string $statePath): static
    {
        return static::make($statePath)
            ->abacModelType($modelType)
            ->default(['logical_operator' => '&&', 'blocks' => []]);
    }

    public function abacModelType(string $modelType): static
    {
        $this->abacModelType = $modelType;

        return $this;
    }

    public function getAbacModelType(): string
    {
        return $this->abacModelType;
    }

    /**
     * Everything the Alpine component needs, serialised to JSON in the view.
     *
     * @return array<string, mixed>
     */
    public function getBuilderConfig(): array
    {
        $attributes = AAuthUtil::getAbacAttributes($this->abacModelType);

        return [
            'attributeOptions' => array_keys($attributes),
            'operatorOptions' => array_map(fn (ABACCondition $c): string => $c->value, ABACCondition::cases()),
            'logicalOptions' => [
                '&&' => __('filament-astart::filament-astart.abac.logical_and'),
                '||' => __('filament-astart::filament-astart.abac.logical_or'),
            ],
            'typeOptions' => [
                'condition' => __('filament-astart::filament-astart.abac.type_condition'),
                'group' => __('filament-astart::filament-astart.abac.type_group'),
            ],
            'valueOptionsMap' => $this->buildValueOptionsMap($attributes),
            'labels' => [
                'top_operator' => __('filament-astart::filament-astart.abac.top_operator'),
                'top_operator_help' => __('filament-astart::filament-astart.abac.top_operator_help'),
                'blocks_help' => __('filament-astart::filament-astart.abac.blocks_help'),
                'block_type' => __('filament-astart::filament-astart.abac.block_type'),
                'attribute' => __('filament-astart::filament-astart.abac.attribute'),
                'operator' => __('filament-astart::filament-astart.abac.operator'),
                'value' => __('filament-astart::filament-astart.abac.value'),
                'group_operator' => __('filament-astart::filament-astart.abac.group_operator'),
                'conditions' => __('filament-astart::filament-astart.abac.conditions'),
                'add_block' => __('filament-astart::filament-astart.abac.add_condition'),
                'add_group' => __('filament-astart::filament-astart.abac.add_group'),
                'add_condition' => __('filament-astart::filament-astart.abac.add_condition'),
                'placeholder_attribute' => __('filament-astart::filament-astart.abac.placeholder_attribute'),
                'placeholder_operator' => __('filament-astart::filament-astart.abac.placeholder_operator'),
                'placeholder_value' => __('filament-astart::filament-astart.abac.placeholder_value'),
                'empty' => __('filament-astart::filament-astart.abac.empty_state'),
                'remove' => __('filament-astart::filament-astart.abac.remove'),
            ],
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $attributes
     * @return array<string, array<int, string>>
     */
    private function buildValueOptionsMap(array $attributes): array
    {
        $map = [];

        foreach ($attributes as $name => $meta) {
            $options = $meta['options'] ?? null;

            if (! is_array($options)) {
                continue;
            }

            $map[$name] = array_values(array_map(
                static fn ($o): string => (string) $o,
                array_filter($options, static fn ($o): bool => is_scalar($o)),
            ));
        }

        return $map;
    }
}
