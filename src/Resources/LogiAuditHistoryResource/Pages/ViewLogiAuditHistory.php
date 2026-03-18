<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\LogiAuditHistoryResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\LogiAuditHistoryResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use Filament\Resources\Pages\ViewRecord;

class ViewLogiAuditHistory extends ViewRecord
{
    use AStartPageLabels;

    protected static string $resource = LogiAuditHistoryResource::class;

    protected static ?string $resourceKey = 'logiaudit_history';

    protected static ?string $pageType = 'view';
}
