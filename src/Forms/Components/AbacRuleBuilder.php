<?php

namespace AuroraWebSoftware\FilamentAstart\Forms\Components;

use AuroraWebSoftware\AAuth\Enums\ABACCondition;
use AuroraWebSoftware\FilamentAstart\Utils\AAuthUtil;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

/**
 * Repeater-based ABAC rule builder for a single ABAC-enabled model_type.
 *
 * Produces a form state that the AbacRuleTransformer can convert to the
 * aauth `rules_json` shape on save. Two-level nesting is supported:
 * a top-level AND/OR plus optional sub-groups (depth = 2). Attribute
 * choices come from the registry whitelist in config/astart-auth.php.
 *
 * Design notes:
 *   - `value` is always a TextInput. When the attribute has registered
 *     options they show as an autocomplete datalist; otherwise the
 *     field is free-text. Using a single field (instead of swapping
 *     between Select / TextInput with the same name) avoids Filament
 *     state path collisions that caused random "add block" misbehaviour.
 *   - Hidden condition fields are marked `dehydrated(false)` so a
 *     block of type=group does not carry leftover `operator` / `value`
 *     into the save payload.
 */
class AbacRuleBuilder
{
    public static function make(string $modelType, ?string $statePath = null): Group
    {
        $attributes = AAuthUtil::getAbacAttributes($modelType);

        $attributeOptions = self::buildAttributeOptions($attributes);
        $operatorOptions = self::buildOperatorOptions();
        $logicalOptions = self::buildLogicalOptions();
        $typeOptions = self::buildTypeOptions();

        return Group::make([
            Select::make('logical_operator')
                ->label(__('filament-astart::filament-astart.abac.top_operator'))
                ->helperText(__('filament-astart::filament-astart.abac.top_operator_help'))
                ->options($logicalOptions)
                ->default('&&')
                ->required()
                ->selectablePlaceholder(false),

            Repeater::make('blocks')
                ->label(__('filament-astart::filament-astart.abac.blocks'))
                ->helperText(__('filament-astart::filament-astart.abac.blocks_help'))
                ->addActionLabel(__('filament-astart::filament-astart.abac.add_block'))
                ->collapsible()
                ->reorderable()
                ->itemLabel(fn (array $state): ?string => self::buildBlockLabel($state))
                ->schema([
                    Select::make('type')
                        ->label(__('filament-astart::filament-astart.abac.block_type'))
                        ->options($typeOptions)
                        ->default('condition')
                        ->required()
                        ->selectablePlaceholder(false)
                        ->live(),

                    // ── condition fields ────────────────────────────────
                    Select::make('attribute')
                        ->label(__('filament-astart::filament-astart.abac.attribute'))
                        ->placeholder(__('filament-astart::filament-astart.abac.placeholder_attribute'))
                        ->options($attributeOptions)
                        ->required(fn (Get $get): bool => $get('type') === 'condition')
                        ->visible(fn (Get $get): bool => $get('type') === 'condition')
                        ->dehydrated(fn (Get $get): bool => $get('type') === 'condition')
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('value', null)),

                    Select::make('operator')
                        ->label(__('filament-astart::filament-astart.abac.operator'))
                        ->placeholder(__('filament-astart::filament-astart.abac.placeholder_operator'))
                        ->options($operatorOptions)
                        ->required(fn (Get $get): bool => $get('type') === 'condition')
                        ->visible(fn (Get $get): bool => $get('type') === 'condition')
                        ->dehydrated(fn (Get $get): bool => $get('type') === 'condition'),

                    TextInput::make('value')
                        ->label(__('filament-astart::filament-astart.abac.value'))
                        ->placeholder(__('filament-astart::filament-astart.abac.placeholder_value'))
                        ->datalist(fn (Get $get): array => array_values(self::valueOptionsFor($modelType, $get('attribute'))))
                        ->required(fn (Get $get): bool => $get('type') === 'condition')
                        ->visible(fn (Get $get): bool => $get('type') === 'condition')
                        ->dehydrated(fn (Get $get): bool => $get('type') === 'condition'),

                    // ── group fields ────────────────────────────────────
                    Select::make('group_operator')
                        ->label(__('filament-astart::filament-astart.abac.group_operator'))
                        ->options($logicalOptions)
                        ->default('||')
                        ->required(fn (Get $get): bool => $get('type') === 'group')
                        ->selectablePlaceholder(false)
                        ->visible(fn (Get $get): bool => $get('type') === 'group')
                        ->dehydrated(fn (Get $get): bool => $get('type') === 'group'),

                    Repeater::make('conditions')
                        ->label(__('filament-astart::filament-astart.abac.conditions'))
                        ->addActionLabel(__('filament-astart::filament-astart.abac.add_condition'))
                        ->collapsible()
                        ->reorderable()
                        ->defaultItems(1)
                        ->visible(fn (Get $get): bool => $get('type') === 'group')
                        ->dehydrated(fn (Get $get): bool => $get('type') === 'group')
                        ->itemLabel(fn (array $state): ?string => self::buildConditionLabel($state))
                        ->schema([
                            Select::make('attribute')
                                ->label(__('filament-astart::filament-astart.abac.attribute'))
                                ->placeholder(__('filament-astart::filament-astart.abac.placeholder_attribute'))
                                ->options($attributeOptions)
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (Set $set) => $set('value', null)),

                            Select::make('operator')
                                ->label(__('filament-astart::filament-astart.abac.operator'))
                                ->placeholder(__('filament-astart::filament-astart.abac.placeholder_operator'))
                                ->options($operatorOptions)
                                ->required(),

                            TextInput::make('value')
                                ->label(__('filament-astart::filament-astart.abac.value'))
                                ->placeholder(__('filament-astart::filament-astart.abac.placeholder_value'))
                                ->datalist(fn (Get $get): array => array_values(self::valueOptionsFor($modelType, $get('attribute'))))
                                ->required(),
                        ]),
                ]),
        ])->statePath($statePath ?? $modelType);
    }

    /**
     * @param  array<string, array<string, mixed>>  $attributes
     * @return array<string, string>
     */
    private static function buildAttributeOptions(array $attributes): array
    {
        $options = [];

        foreach (array_keys($attributes) as $name) {
            $options[$name] = $name;
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private static function buildOperatorOptions(): array
    {
        $options = [];

        foreach (ABACCondition::cases() as $case) {
            $options[$case->value] = $case->value;
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private static function buildLogicalOptions(): array
    {
        return [
            '&&' => __('filament-astart::filament-astart.abac.logical_and'),
            '||' => __('filament-astart::filament-astart.abac.logical_or'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function buildTypeOptions(): array
    {
        return [
            'condition' => __('filament-astart::filament-astart.abac.type_condition'),
            'group' => __('filament-astart::filament-astart.abac.type_group'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function valueOptionsFor(string $modelType, mixed $attribute): array
    {
        if (! is_string($attribute) || $attribute === '') {
            return [];
        }

        $options = AAuthUtil::getAbacAttributeOptions($modelType, $attribute) ?? [];
        $mapped = [];

        foreach ($options as $option) {
            if (is_scalar($option)) {
                $mapped[] = (string) $option;
            }
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private static function buildBlockLabel(array $state): ?string
    {
        $type = $state['type'] ?? null;

        if ($type === 'group') {
            $count = is_array($state['conditions'] ?? null) ? count($state['conditions']) : 0;

            return sprintf('[%s] (%d)', $state['group_operator'] ?? '||', $count);
        }

        return self::buildConditionLabel($state);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private static function buildConditionLabel(array $state): ?string
    {
        $attribute = $state['attribute'] ?? null;
        $operator = $state['operator'] ?? null;
        $value = $state['value'] ?? null;

        if (! $attribute || ! $operator) {
            return null;
        }

        return sprintf('%s %s %s', $attribute, $operator, $value ?? '');
    }
}
