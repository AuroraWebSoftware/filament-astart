<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\LogiAuditLogResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\LogiAuditLogResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use Filament\Resources\Pages\ViewRecord;

class ViewLogiAuditLog extends ViewRecord
{
    use AStartPageLabels;

    protected static string $resource = LogiAuditLogResource::class;

    protected static ?string $resourceKey = 'logiaudit_log';

    protected static ?string $pageType = 'view';
}
