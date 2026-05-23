@php
    $statePath = $getStatePath();
    $config = $field->getBuilderConfig();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @assets
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('abacRuleBuilder', (config, initial) => ({
                    state: initial,
                    config: config,

                    init() {
                        if (! this.state || typeof this.state !== 'object' || Array.isArray(this.state)) {
                            this.state = { logical_operator: '&&', blocks: [] };
                        }
                        if (! this.state.logical_operator) {
                            this.state.logical_operator = '&&';
                        }
                        if (! Array.isArray(this.state.blocks)) {
                            this.state.blocks = [];
                        }
                        this.state.blocks.forEach((b) => {
                            if (! b._uid) b._uid = this.uid();
                            if (b.type === 'group' && Array.isArray(b.conditions)) {
                                b.conditions.forEach((c) => {
                                    if (! c._uid) c._uid = this.uid();
                                });
                            }
                        });
                    },

                    uid() {
                        return Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
                    },

                    addCondition() {
                        this.state.blocks.push({ _uid: this.uid(), type: 'condition', attribute: '', operator: '', value: '' });
                    },

                    addGroup() {
                        this.state.blocks.push({
                            _uid: this.uid(),
                            type: 'group',
                            group_operator: '||',
                            conditions: [{ _uid: this.uid(), attribute: '', operator: '', value: '' }],
                        });
                    },

                    removeBlock(index) {
                        this.state.blocks.splice(index, 1);
                    },

                    addGroupCondition(blockIndex) {
                        this.state.blocks[blockIndex].conditions.push({ _uid: this.uid(), attribute: '', operator: '', value: '' });
                    },

                    removeGroupCondition(blockIndex, condIndex) {
                        this.state.blocks[blockIndex].conditions.splice(condIndex, 1);
                    },

                    valueOptions(attribute) {
                        return this.config.valueOptionsMap[attribute] || [];
                    },
                }));
            });
        </script>
    @endassets

    <div
        x-data="abacRuleBuilder(@js($config), $wire.$entangle(@js($statePath)))"
        class="fi-abac"
    >
        {{-- Top-level operator --}}
        <div class="fi-abac-field">
            <span class="fi-abac-label" x-text="config.labels.top_operator"></span>
            <select x-model="state.logical_operator" class="fi-abac-control fi-abac-block-type">
                @foreach ($config['logicalOptions'] as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
            <span class="fi-abac-help" x-text="config.labels.top_operator_help"></span>
        </div>

        {{-- Empty state --}}
        <p x-show="state.blocks.length === 0" class="fi-abac-empty" x-text="config.labels.empty"></p>

        {{-- Blocks --}}
        <template x-for="(block, bi) in state.blocks" :key="block._uid">
            <div class="fi-abac-block">
                <div class="fi-abac-block-header">
                    <span class="fi-abac-block-badge" x-text="block.type === 'group' ? config.typeOptions.group : config.typeOptions.condition"></span>

                    <button type="button" class="fi-abac-remove" @click="removeBlock(bi)" x-text="config.labels.remove"></button>
                </div>

                {{-- Condition --}}
                <div x-show="block.type === 'condition'" class="fi-abac-row">
                    <select x-model="block.attribute" class="fi-abac-control">
                        <option value="">{{ $config['labels']['placeholder_attribute'] }}</option>
                        @foreach ($config['attributeOptions'] as $attr)
                            <option value="{{ $attr }}">{{ $attr }}</option>
                        @endforeach
                    </select>

                    <select x-model="block.operator" class="fi-abac-control">
                        <option value="">{{ $config['labels']['placeholder_operator'] }}</option>
                        @foreach ($config['operatorOptions'] as $op)
                            <option value="{{ $op }}">{{ $op }}</option>
                        @endforeach
                    </select>

                    <input
                        type="text"
                        x-model="block.value"
                        :placeholder="config.labels.placeholder_value"
                        :list="'abac-v-' + block._uid"
                        class="fi-abac-control"
                    />
                    <datalist :id="'abac-v-' + block._uid">
                        <template x-for="opt in valueOptions(block.attribute)" :key="opt">
                            <option :value="opt"></option>
                        </template>
                    </datalist>
                </div>

                {{-- Group --}}
                <div x-show="block.type === 'group'" class="fi-abac-group">
                    <select x-model="block.group_operator" class="fi-abac-control fi-abac-block-type">
                        @foreach ($config['logicalOptions'] as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <template x-for="(cond, ci) in block.conditions" :key="cond._uid">
                        <div class="fi-abac-row fi-abac-row-group">
                            <select x-model="cond.attribute" class="fi-abac-control">
                                <option value="">{{ $config['labels']['placeholder_attribute'] }}</option>
                                @foreach ($config['attributeOptions'] as $attr)
                                    <option value="{{ $attr }}">{{ $attr }}</option>
                                @endforeach
                            </select>

                            <select x-model="cond.operator" class="fi-abac-control">
                                <option value="">{{ $config['labels']['placeholder_operator'] }}</option>
                                @foreach ($config['operatorOptions'] as $op)
                                    <option value="{{ $op }}">{{ $op }}</option>
                                @endforeach
                            </select>

                            <input
                                type="text"
                                x-model="cond.value"
                                :placeholder="config.labels.placeholder_value"
                                :list="'abac-cv-' + cond._uid"
                                class="fi-abac-control"
                            />
                            <datalist :id="'abac-cv-' + cond._uid">
                                <template x-for="opt in valueOptions(cond.attribute)" :key="opt">
                                    <option :value="opt"></option>
                                </template>
                            </datalist>

                            <button type="button" class="fi-abac-remove" @click="removeGroupCondition(bi, ci)" x-text="config.labels.remove"></button>
                        </div>
                    </template>

                    <button type="button" class="fi-abac-link" @click="addGroupCondition(bi)" x-text="config.labels.add_condition"></button>
                </div>
            </div>
        </template>

        {{-- Add actions --}}
        <div class="fi-abac-actions">
            <button type="button" class="fi-abac-btn fi-abac-btn-primary" @click="addCondition()" x-text="config.labels.add_block"></button>
            <button type="button" class="fi-abac-btn fi-abac-btn-secondary" @click="addGroup()" x-text="config.labels.add_group"></button>
        </div>
    </div>
</x-dynamic-component>
