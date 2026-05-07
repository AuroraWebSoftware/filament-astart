<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\LogiAuditHistoryResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\LogiAuditHistoryResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use Filament\Resources\Pages\ListRecords;

class ListLogiAuditHistories extends ListRecords
{
    use AStartPageLabels;

    protected static string $resource = LogiAuditHistoryResource::class;

    protected static ?string $resourceKey = 'logiaudit_history';

    protected static ?string $pageType = 'list';
}
