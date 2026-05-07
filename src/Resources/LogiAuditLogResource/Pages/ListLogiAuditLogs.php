<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\LogiAuditLogResource\Pages;

use AuroraWebSoftware\FilamentAstart\Resources\LogiAuditLogResource;
use AuroraWebSoftware\FilamentAstart\Resources\LogiAuditLogResource\Widgets\LogiAuditLogStatsWidget;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use Filament\Resources\Pages\ListRecords;

class ListLogiAuditLogs extends ListRecords
{
    use AStartPageLabels;

    protected static string $resource = LogiAuditLogResource::class;

    protected static ?string $resourceKey = 'logiaudit_log';

    protected static ?string $pageType = 'list';

    protected function getHeaderWidgets(): array
    {
        return [
            LogiAuditLogStatsWidget::class,
        ];
    }
}
