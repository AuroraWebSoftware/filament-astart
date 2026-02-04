<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\OrganizationTreeResource\Pages;

use AuroraWebSoftware\FilamentAstart\Model\OrganizationNode;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationTreeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\Page;

class ListOrganizationTrees extends Page
{
    protected static string $resource = OrganizationTreeResource::class;

    protected string $view = 'filament-astart::pages.organization-node-tree';

    public array $expandedNodes = [];

    public array $loadedChildren = [];

    protected array $childrenCache = [];

    public string $search = '';

    public bool $showScopeLevel = false;

    public bool $showPath = false;

    public bool $showChildCount = false;

    public ?int $highlightedNodeId = null;

    public function mount(): void
    {
        $this->expandedNodes = [];
        $this->loadedChildren = [];
    }

    public function getTitle(): string
    {
        return __('filament-astart::filament-astart.resources.organization_tree.pages.list.title');
    }

    public function getHeading(): string
    {
        return __('filament-astart::filament-astart.resources.organization_tree.pages.list.heading');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('filament-astart::filament-astart.resources.organization_tree.tree.add_root_node'))
                ->url(fn () => static::getResource()::getUrl('create')),
        ];
    }

    public function getRootNodes()
    {
        return OrganizationNode::with(['organizationScope'])
            ->withCount('children')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();
    }

    public function getChildrenFor(int $nodeId)
    {
        if (isset($this->childrenCache[$nodeId])) {
            return $this->childrenCache[$nodeId];
        }

        $children = OrganizationNode::with(['organizationScope'])
            ->withCount('children')
            ->where('parent_id', $nodeId)
            ->orderBy('name')
            ->get();

        $this->childrenCache[$nodeId] = $children;

        return $children;
    }

    public function toggleNode(int $nodeId): void
    {
        $this->highlightedNodeId = null;

        if (in_array($nodeId, $this->expandedNodes)) {
            $this->expandedNodes = array_values(array_diff($this->expandedNodes, [$nodeId]));
        } else {
            $this->expandedNodes[] = $nodeId;

            if (! in_array($nodeId, $this->loadedChildren)) {
                $this->loadedChildren[] = $nodeId;
            }
        }
    }

    public function isChildrenLoaded(int $nodeId): bool
    {
        return in_array($nodeId, $this->loadedChildren);
    }

    public function getTotalNodeCount(): int
    {
        return OrganizationNode::count();
    }

    public function getSearchResults()
    {
        if (strlen($this->search) < 2) {
            return collect();
        }

        return OrganizationNode::with(['organizationScope', 'parent'])
            ->withCount('children')
            ->where('name', 'ilike', '%' . $this->search . '%')
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    public function goToNode(int $nodeId): void
    {
        $node = OrganizationNode::find($nodeId);
        if ($node && $node->path) {
            $pathIds = explode('/', $node->path);

            foreach ($pathIds as $parentId) {
                if (is_numeric($parentId) && ! in_array((int) $parentId, $this->expandedNodes)) {
                    $this->expandedNodes[] = (int) $parentId;
                    if (! in_array((int) $parentId, $this->loadedChildren)) {
                        $this->loadedChildren[] = (int) $parentId;
                    }
                }
            }
        }

        $this->highlightedNodeId = $nodeId;

        $this->search = '';
    }

    public function isHighlighted(int $nodeId): bool
    {
        return $this->highlightedNodeId === $nodeId;
    }

    public function clearHighlight(): void
    {
        $this->highlightedNodeId = null;
    }

    public function isExpanded(int $nodeId): bool
    {
        return in_array($nodeId, $this->expandedNodes);
    }

    public function expandAll(): void
    {
        $rootIds = OrganizationNode::whereNull('parent_id')->pluck('id')->toArray();
        $level1Ids = OrganizationNode::whereIn('parent_id', $rootIds)->pluck('id')->toArray();

        $this->expandedNodes = array_merge($rootIds, $level1Ids);
        $this->loadedChildren = array_merge($this->loadedChildren, $rootIds, $level1Ids);
    }

    public function collapseAll(): void
    {
        $this->expandedNodes = [];
    }

    public function getCreateUrl(?int $parentId = null): string
    {
        if ($parentId) {
            return static::getResource()::getUrl('create', ['parent_id' => $parentId]);
        }

        return static::getResource()::getUrl('create');
    }

    public function getEditUrl(int $nodeId): string
    {
        return static::getResource()::getUrl('edit', ['record' => $nodeId]);
    }
}
