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
            'group astart-tree-row',
            'astart-tree-row-highlighted' => $isHighlighted,
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
            wire:click.stop="toggleNode({{ $node->id }})"
            @class([
                'astart-expand-btn-mobile sm:astart-expand-btn',
                'cursor-default invisible' => !$hasChildren,
            ])
            @if(!$hasChildren) disabled @endif
        >
            @if($hasChildren)
                <x-heroicon-o-chevron-right @class([
                    'w-3.5 h-3.5 sm:w-4 sm:h-4 fi-color-gray transition-transform duration-200',
                    'rotate-90' => $isExpanded,
                ]) />
            @endif
        </button>

        {{-- Node Icon --}}
        <div class="flex-shrink-0">
            @if($hasChildren)
                <x-heroicon-o-folder class="astart-folder-icon-mobile sm:astart-folder-icon" />
            @else
                <x-heroicon-o-document class="astart-document-icon-mobile sm:astart-document-icon" />
            @endif
        </div>

        {{-- Node Info --}}
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-1 sm:gap-2">
                <span class="font-medium text-sm sm:text-base fi-color-text truncate">
                    {{ $node->name }}
                    @if($this->showChildCount && $hasChildren)
                        <span class="font-normal fi-color-gray">({{ $node->children_count }})</span>
                    @endif
                </span>
                @if($node->organizationScope)
                    <span class="fi-badge fi-badge-size-xs sm:fi-badge-size-sm fi-color-primary">
                        {{ $node->organizationScope->name }}
                        @if($this->showScopeLevel && $node->organizationScope->level)
                            <span class="ml-0.5 sm:ml-1 opacity-75">({{ $node->organizationScope->level }})</span>
                        @endif
                    </span>
                @endif
            </div>
            @if($this->showPath && $node->path)
                <p class="text-[10px] sm:text-xs fi-color-gray truncate mt-0.5">{{ $node->path }}</p>
            @endif
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-2 sm:gap-1 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity flex-shrink-0" wire:click.stop>
            @if($canAddChild)
                <x-filament::icon-button
                    :href="$this->getCreateUrl($node->id)"
                    tag="a"
                    size="xs"
                    color="primary"
                    icon="heroicon-o-plus"
                    class="!w-6 !h-6 sm:!w-8 sm:!h-8"
                />
            @endif

            <x-filament::icon-button
                :href="$this->getEditUrl($node->id)"
                tag="a"
                size="xs"
                color="gray"
                icon="heroicon-o-pencil"
                class="!w-6 !h-6 sm:!w-8 sm:!h-8"
            />
        </div>
    </div>

    {{-- Children --}}
    @if($hasChildren && $isExpanded)
        <ul wire:key="children-{{ $node->id }}">
            @if($isLoaded)
                @foreach($this->getChildrenFor($node->id) as $child)
                    @include('filament-astart::components.organization-node-tree-item', ['node' => $child, 'level' => $level + 1])
                @endforeach
            @else
                <li class="astart-loading" style="padding-left: {{ ($level + 1) * 12 + 4 }}px;">
                    <x-filament::loading-indicator class="w-4 h-4" />
                    <span>{{ __('filament-astart::filament-astart.resources.organization_tree.tree.loading') }}</span>
                </li>
            @endif
        </ul>
    @endif
</li>
