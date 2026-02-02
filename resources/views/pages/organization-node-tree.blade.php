<x-filament-panels::page>
    <div class="space-y-3 sm:space-y-4">
        {{-- Toolbar --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-3 sm:p-4">
            <div class="flex flex-col gap-3">
                {{-- Top Row: Search & Stats --}}
                <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                    {{-- Search --}}
                    <div class="relative flex-1 max-w-md">
                        <div class="relative">
                            <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="search"
                                placeholder="{{ __('filament-astart::filament-astart.resources.organization_tree.tree.search_placeholder') }}"
                                class="w-full pl-9 pr-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                            @if($search)
                                <button
                                    type="button"
                                    wire:click="$set('search', '')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                >
                                    <x-heroicon-o-x-mark class="w-4 h-4" />
                                </button>
                            @endif
                        </div>

                        {{-- Search Results Dropdown --}}
                        @if(strlen($search) >= 2)
                            @php $searchResults = $this->getSearchResults(); @endphp
                            <div class="absolute z-50 top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-gray-950/5 dark:ring-white/10 max-h-64 overflow-y-auto">
                                @if($searchResults->isEmpty())
                                    <div class="p-3 text-sm text-gray-500 dark:text-gray-400 text-center">
                                        {{ __('filament-astart::filament-astart.resources.organization_tree.tree.no_results') }}
                                    </div>
                                @else
                                    @foreach($searchResults as $result)
                                        <button
                                            type="button"
                                            wire:click="goToNode({{ $result->id }})"
                                            class="w-full px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2 text-sm"
                                        >
                                            <x-heroicon-o-folder class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium text-gray-900 dark:text-white truncate">{{ $result->name }}</div>
                                                @if($result->parent)
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $result->parent->name }}</div>
                                                @endif
                                            </div>
                                            @if($result->organizationScope)
                                                <span class="text-xs px-1.5 py-0.5 rounded bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300 flex-shrink-0">
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
                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-chart-bar class="w-4 h-4" />
                        <span>{{ __('filament-astart::filament-astart.resources.organization_tree.tree.total_nodes') }}: <strong class="text-gray-700 dark:text-gray-300">{{ $this->getTotalNodeCount() }}</strong></span>
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

                        {{-- Desktop: With text --}}
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
                    <div class="flex flex-wrap items-center gap-3 sm:gap-5">
                        <label class="inline-flex items-center gap-1.5 sm:gap-2 text-xs sm:text-sm cursor-pointer">
                            <x-filament::input.checkbox wire:model.live="showScopeLevel" />
                            <span class="text-gray-600 dark:text-gray-400">{{ __('filament-astart::filament-astart.resources.organization_tree.tree.show_scope_level') }}</span>
                        </label>

                        <label class="inline-flex items-center gap-1.5 sm:gap-2 text-xs sm:text-sm cursor-pointer">
                            <x-filament::input.checkbox wire:model.live="showPath" />
                            <span class="text-gray-600 dark:text-gray-400">{{ __('filament-astart::filament-astart.resources.organization_tree.tree.show_path') }}</span>
                        </label>

                        <label class="inline-flex items-center gap-1.5 sm:gap-2 text-xs sm:text-sm cursor-pointer">
                            <x-filament::input.checkbox wire:model.live="showChildCount" />
                            <span class="text-gray-600 dark:text-gray-400">{{ __('filament-astart::filament-astart.resources.organization_tree.tree.show_child_count') }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tree Container --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="p-3 sm:p-4">
                @php
                    $rootNodes = $this->getRootNodes();
                @endphp

                @if($rootNodes->isEmpty())
                    {{-- Empty State --}}
                    <div class="text-center py-12 sm:py-16">
                        <div class="mx-auto w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                            <x-heroicon-o-building-office-2 class="w-8 h-8 sm:w-10 sm:h-10 text-gray-400" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">
                            {{ __('filament-astart::filament-astart.resources.organization_tree.tree.no_nodes_yet') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-sm mx-auto">
                            {{ __('filament-astart::filament-astart.resources.organization_tree.tree.empty_state_description') }}
                        </p>
                        <a
                            href="{{ $this->getCreateUrl() }}"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors font-medium text-sm"
                        >
                            <x-heroicon-o-plus class="w-5 h-5" />
                            {{ __('filament-astart::filament-astart.resources.organization_tree.tree.add_root_node') }}
                        </a>
                    </div>
                @else
                    <ul class="space-y-0.5 sm:space-y-1">
                        @foreach($rootNodes as $node)
                            @include('filament-astart::components.organization-node-tree-item', ['node' => $node, 'level' => 0])
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
