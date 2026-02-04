<x-filament-panels::page>
    <div class="space-y-3 sm:space-y-4">
        {{-- Toolbar --}}
        <div class="fi-section rounded-xl p-3 sm:p-4">
            <div class="flex flex-col gap-3">
                {{-- Top Row: Search & Stats --}}
                <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                    {{-- Search --}}
                    <div class="relative flex-1 max-w-md">
                        <div class="relative">
                            <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 fi-color-gray" />
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="search"
                                placeholder="{{ __('filament-astart::filament-astart.resources.organization_tree.tree.search_placeholder') }}"
                                class="astart-search-input"
                            />
                            @if($search)
                                <button
                                    type="button"
                                    wire:click="$set('search', '')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 fi-color-gray hover:text-gray-600"
                                >
                                    <x-heroicon-o-x-mark class="w-4 h-4" />
                                </button>
                            @endif
                        </div>

                        {{-- Search Results Dropdown --}}
                        @if(strlen($search) >= 2)
                            @php $searchResults = $this->getSearchResults(); @endphp
                            <div class="astart-search-dropdown">
                                @if($searchResults->isEmpty())
                                    <div class="p-3 text-sm fi-color-gray text-center">
                                        {{ __('filament-astart::filament-astart.resources.organization_tree.tree.no_results') }}
                                    </div>
                                @else
                                    @foreach($searchResults as $result)
                                        <button
                                            type="button"
                                            wire:click="goToNode({{ $result->id }})"
                                            class="astart-dropdown-item"
                                        >
                                            <x-heroicon-o-folder class="w-4 h-4 fi-color-gray flex-shrink-0" />
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium fi-color-text truncate">{{ $result->name }}</div>
                                                @if($result->parent)
                                                    <div class="text-xs fi-color-gray truncate">{{ $result->parent->name }}</div>
                                                @endif
                                            </div>
                                            @if($result->organizationScope)
                                                <span class="fi-badge fi-badge-size-sm fi-color-primary flex-shrink-0">
                                                    {{ $result->organizationScope->name }}
                                                </span>
                                            @endif
                                        </button>
                                    @endforeach
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Stats --}}
                    <div class="flex items-center gap-2 text-sm fi-color-gray">
                        <x-heroicon-o-chart-bar class="w-4 h-4" />
                        <span>{{ __('filament-astart::filament-astart.resources.organization_tree.tree.total_nodes') }}: <strong class="fi-color-text">{{ $this->getTotalNodeCount() }}</strong></span>
                    </div>
                </div>

                {{-- Bottom Row: Actions & Options --}}
                <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                    {{-- Expand/Collapse Buttons --}}
                    <div class="flex items-center gap-2">
                        {{-- Mobile: Icon only --}}
                        <x-filament::button
                            size="sm"
                            color="gray"
                            wire:click="expandAll"
                            icon="heroicon-o-arrows-pointing-out"
                            class="sm:hidden"
                        />
                        <x-filament::button
                            size="sm"
                            color="gray"
                            wire:click="collapseAll"
                            icon="heroicon-o-arrows-pointing-in"
                            class="sm:hidden"
                        />

                        {{-- Desktop: With labels --}}
                        <x-filament::button
                            size="sm"
                            color="gray"
                            wire:click="expandAll"
                            icon="heroicon-o-arrows-pointing-out"
                            class="hidden sm:inline-flex"
                        >
                            {{ __('filament-astart::filament-astart.resources.organization_tree.tree.expand_all') }}
                        </x-filament::button>

                        <x-filament::button
                            size="sm"
                            color="gray"
                            wire:click="collapseAll"
                            icon="heroicon-o-arrows-pointing-in"
                            class="hidden sm:inline-flex"
                        >
                            {{ __('filament-astart::filament-astart.resources.organization_tree.tree.collapse_all') }}
                        </x-filament::button>
                    </div>

                    {{-- Display Options --}}
                    <div class="flex flex-wrap items-center gap-4">
                        <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                            <x-filament::input.checkbox wire:model.live="showScopeLevel" />
                            <span class="fi-color-gray">{{ __('filament-astart::filament-astart.resources.organization_tree.tree.show_scope_level') }}</span>
                        </label>

                        <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                            <x-filament::input.checkbox wire:model.live="showPath" />
                            <span class="fi-color-gray">{{ __('filament-astart::filament-astart.resources.organization_tree.tree.show_path') }}</span>
                        </label>

                        <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                            <x-filament::input.checkbox wire:model.live="showChildCount" />
                            <span class="fi-color-gray">{{ __('filament-astart::filament-astart.resources.organization_tree.tree.show_child_count') }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tree Container --}}
        <div class="fi-section rounded-xl p-3 sm:p-4">
            @php $rootNodes = $this->getRootNodes(); @endphp

            @if($rootNodes->isEmpty())
                {{-- Empty State --}}
                <div class="text-center py-12">
                    <div class="astart-empty-icon">
                        <x-heroicon-o-building-office-2 class="w-8 h-8 fi-color-gray" />
                    </div>
                    <h3 class="text-lg font-medium fi-color-text mb-1">
                        {{ __('filament-astart::filament-astart.resources.organization_tree.tree.no_nodes_yet') }}
                    </h3>
                    <p class="text-sm fi-color-gray mb-6 max-w-sm mx-auto">
                        {{ __('filament-astart::filament-astart.resources.organization_tree.tree.empty_state_description') }}
                    </p>
                    <x-filament::button
                        :href="$this->getCreateUrl()"
                        tag="a"
                        icon="heroicon-o-plus"
                    >
                        {{ __('filament-astart::filament-astart.resources.organization_tree.tree.add_root_node') }}
                    </x-filament::button>
                </div>
            @else
                <ul class="space-y-1">
                    @foreach($rootNodes as $node)
                        @include('filament-astart::components.organization-node-tree-item', ['node' => $node, 'level' => 0])
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-filament-panels::page>
