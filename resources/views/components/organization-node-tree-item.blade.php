@props(['node', 'level' => 0])

@php
    $hasChildren = $node->children_count > 0;
    $isExpanded = $this->isExpanded($node->id);
    $isLoaded = $this->isChildrenLoaded($node->id);
    $isHighlighted = $this->isHighlighted($node->id);
    $indentMobile = $level * 12;
    $indentDesktop = $level * 24;
    $canAddChild = $node->availableScopes()?->isNotEmpty() ?? false;
@endphp

<li class="select-none" @if($isHighlighted) id="highlighted-node" @endif>
    <div
        wire:click="toggleNode({{ $node->id }})"
        @class([
            'group flex items-center gap-1 sm:gap-2 py-1.5 sm:py-2 px-1 sm:px-3 rounded-lg transition-colors cursor-pointer',
            'hover:bg-gray-100 dark:hover:bg-gray-800' => !$isHighlighted,
            'bg-primary-100 dark:bg-primary-900/50 ring-2 ring-primary-500 dark:ring-primary-400' => $isHighlighted,
        ])
        style="padding-left: {{ $indentMobile + 4 }}px;"
        @if($indentDesktop > 0)
        x-data
        x-bind:style="window.innerWidth >= 640 ? 'padding-left: {{ $indentDesktop + 12 }}px' : 'padding-left: {{ $indentMobile + 4 }}px'"
        @endif
        @if($isHighlighted)
        x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'center' })"
        @endif
    >
        {{-- Expand/Collapse Button --}}
        <button
            type="button"
            wire:click="toggleNode({{ $node->id }})"
            @class([
                'w-5 h-5 sm:w-6 sm:h-6 flex-shrink-0 flex items-center justify-center rounded transition-colors',
                'hover:bg-gray-200 dark:hover:bg-gray-700 active:bg-gray-300' => $hasChildren,
                'cursor-default invisible' => !$hasChildren,
            ])
            @if(!$hasChildren) disabled @endif
        >
            @if($hasChildren)
                <x-heroicon-o-chevron-right @class([
                    'w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-500 transition-transform duration-200',
                    'rotate-90' => $isExpanded,
                ]) />
            @endif
        </button>

        {{-- Node Icon --}}
        <div class="flex-shrink-0">
            @if($hasChildren)
                <x-heroicon-o-folder class="w-4 h-4 sm:w-5 sm:h-5 text-primary-500 dark:text-primary-400" />
            @else
                <x-heroicon-o-document class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 dark:text-gray-500" />
            @endif
        </div>

        {{-- Node Info --}}
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-1 sm:gap-2">
                <span class="font-medium text-sm sm:text-base text-gray-900 dark:text-white truncate">
                    {{ $node->name }}
                    @if($this->showChildCount && $hasChildren)
                        <span class="font-normal text-gray-400 dark:text-gray-500">({{ $node->children_count }})</span>
                    @endif
                </span>
                @if($node->organizationScope)
                    <span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded-full text-[10px] sm:text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300">
                        {{ $node->organizationScope->name }}
                        @if($this->showScopeLevel && $node->organizationScope->level)
                            <span class="ml-0.5 sm:ml-1 opacity-75">({{ $node->organizationScope->level }})</span>
                        @endif
                    </span>
                @endif
            </div>
            @if($this->showPath && $node->path)
                <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 truncate">
                    {{ $node->path }}
                </p>
            @endif
        </div>

        {{-- Actions - Mobilde her zaman görünür, masaüstünde hover'da --}}
        <div class="flex items-center gap-1 sm:gap-2 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity flex-shrink-0" wire:click.stop>
            {{-- Add Child - sadece alt seviye eklenebiliyorsa göster --}}
            @if($canAddChild)
                <a
                    href="{{ $this->getCreateUrl($node->id) }}"
                    class="inline-flex items-center justify-center p-1.5 sm:px-3 sm:py-1.5 rounded-md sm:rounded-lg text-xs sm:text-sm font-medium bg-primary-500 text-white hover:bg-primary-600 active:bg-primary-700 dark:bg-primary-600 dark:hover:bg-primary-500 transition-colors"
                    title="{{ __('filament-astart::filament-astart.resources.organization_tree.tree.add_child_node') }}"
                >
                    <x-heroicon-o-plus class="w-4 h-4" />
                    <span class="hidden lg:inline ml-1">{{ __('filament-astart::filament-astart.resources.organization_tree.tree.add_child_node') }}</span>
                </a>
            @endif

            {{-- Edit --}}
            <a
                href="{{ $this->getEditUrl($node->id) }}"
                class="inline-flex items-center justify-center p-1.5 sm:px-3 sm:py-1.5 rounded-md sm:rounded-lg text-xs sm:text-sm font-medium bg-warning-500 text-white hover:bg-warning-600 active:bg-warning-700 dark:bg-warning-600 dark:hover:bg-warning-500 transition-colors"
                title="{{ __('filament-astart::filament-astart.resources.organization_tree.tree.edit_node') }}"
            >
                <x-heroicon-o-pencil class="w-4 h-4" />
                <span class="hidden lg:inline ml-1">{{ __('filament-astart::filament-astart.resources.organization_tree.tree.edit_node') }}</span>
            </a>
        </div>
    </div>

    {{-- Children - Lazy Loading --}}
    @if($hasChildren && $isExpanded)
        <ul class="space-y-0.5 sm:space-y-1" wire:key="children-{{ $node->id }}">
            @if($isLoaded)
                @foreach($this->getChildrenFor($node->id) as $child)
                    @include('filament-astart::components.organization-node-tree-item', ['node' => $child, 'level' => $level + 1])
                @endforeach
            @else
                {{-- Loading indicator --}}
                <li class="flex items-center gap-2 py-2 text-gray-400 dark:text-gray-500 text-sm" style="padding-left: {{ ($level + 1) * 12 + 4 }}px;">
                    <x-filament::loading-indicator class="w-4 h-4" />
                    <span>{{ __('filament-astart::filament-astart.resources.organization_tree.tree.loading') }}</span>
                </li>
            @endif
        </ul>
    @endif
</li>
