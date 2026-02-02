<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource\Pages;

use AuroraWebSoftware\FilamentAstart\Model\OrganizationNode;
use AuroraWebSoftware\FilamentAstart\Resources\OrganizationNodeResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use Filament\Resources\Pages\CreateRecord;

class CreateOrganizationNode extends CreateRecord
{
    use AStartPageLabels;

    protected static string $resource = OrganizationNodeResource::class;

    protected static ?string $resourceKey = 'organization_node';

    protected static ?string $pageType = 'create';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $parentPath = null;

        if (! empty($data['parent_id'])) {
            $parentPath = OrganizationNode::find($data['parent_id'])?->path;
        }

        $data['path'] = trim($parentPath ? $parentPath . '/' : '') . 'temp';

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var OrganizationNode $record */
        $record = $this->record;
        $parentPath = $record->parent?->path;

        $record->path = trim($parentPath ? $parentPath . '/' : '') . $record->id;
        $record->save();
    }
}
