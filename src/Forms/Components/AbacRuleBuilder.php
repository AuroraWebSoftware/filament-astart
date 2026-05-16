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
                ->defaultItems(0)
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
                        ->required()
                        ->visible(fn (Get $get): bool => $get('type') === 'condition')
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('value', null)),

                    Select::make('operator')
                        ->label(__('filament-astart::filament-astart.abac.operator'))
                        ->placeholder(__('filament-astart::filament-astart.abac.placeholder_operator'))
                        ->options($operatorOptions)
                        ->required()
                        ->visible(fn (Get $get): bool => $get('type') === 'condition'),

                    Select::make('value')
                        ->label(__('filament-astart::filament-astart.abac.value'))
                        ->placeholder(__('filament-astart::filament-astart.abac.placeholder_value'))
                        ->options(fn (Get $get): array => self::valueOptionsFor($modelType, $get('attribute')))
                        ->required()
                        ->visible(fn (Get $get): bool => $get('type') === 'condition' && self::hasValueOptions($modelType, $get('attribute')))
                        ->dehydrated(fn (Get $get): bool => $get('type') === 'condition' && self::hasValueOptions($modelType, $get('attribute'))),

                    TextInput::make('value')
                        ->label(__('filament-astart::filament-astart.abac.value'))
                        ->required()
                        ->visible(fn (Get $get): bool => $get('type') === 'condition' && ! self::hasValueOptions($modelType, $get('attribute')))
                        ->dehydrated(fn (Get $get): bool => $get('type') === 'condition' && ! self::hasValueOptions($modelType, $get('attribute'))),

                    // ── group fields ────────────────────────────────────
                    Select::make('logical_operator')
                        ->label(__('filament-astart::filament-astart.abac.group_operator'))
                        ->options($logicalOptions)
                        ->default('||')
                        ->required()
                        ->selectablePlaceholder(false)
                        ->visible(fn (Get $get): bool => $get('type') === 'group'),

                    Repeater::make('conditions')
                        ->label(__('filament-astart::filament-astart.abac.conditions'))
                        ->addActionLabel(__('filament-astart::filament-astart.abac.add_condition'))
                        ->collapsible()
                        ->reorderable()
                        ->defaultItems(1)
                        ->visible(fn (Get $get): bool => $get('type') === 'group')
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

                            Select::make('value')
                                ->label(__('filament-astart::filament-astart.abac.value'))
                                ->placeholder(__('filament-astart::filament-astart.abac.placeholder_value'))
                                ->options(fn (Get $get): array => self::valueOptionsFor($modelType, $get('attribute')))
                                ->required()
                                ->visible(fn (Get $get): bool => self::hasValueOptions($modelType, $get('attribute')))
                                ->dehydrated(fn (Get $get): bool => self::hasValueOptions($modelType, $get('attribute'))),

                            TextInput::make('value')
                                ->label(__('filament-astart::filament-astart.abac.value'))
                                ->required()
                                ->visible(fn (Get $get): bool => ! self::hasValueOptions($modelType, $get('attribute')))
                                ->dehydrated(fn (Get $get): bool => ! self::hasValueOptions($modelType, $get('attribute'))),
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
     * @return array<string, string>
     */
    private static function valueOptionsFor(string $modelType, mixed $attribute): array
    {
        if (! is_string($attribute) || $attribute === '') {
            return [];
        }

        $options = AAuthUtil::getAbacAttributeOptions($modelType, $attribute) ?? [];

        $mapped = [];

        foreach ($options as $option) {
            $key = is_scalar($option) ? (string) $option : null;

            if ($key !== null) {
                $mapped[$key] = $key;
            }
        }

        return $mapped;
    }

    private static function hasValueOptions(string $modelType, mixed $attribute): bool
    {
        if (! is_string($attribute) || $attribute === '') {
            return false;
        }

        return AAuthUtil::getAbacAttributeOptions($modelType, $attribute) !== null;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private static function buildBlockLabel(array $state): ?string
    {
        $type = $state['type'] ?? null;

        if ($type === 'group') {
            $count = is_array($state['conditions'] ?? null) ? count($state['conditions']) : 0;

            return sprintf('[%s] (%d)', $state['logical_operator'] ?? '||', $count);
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
