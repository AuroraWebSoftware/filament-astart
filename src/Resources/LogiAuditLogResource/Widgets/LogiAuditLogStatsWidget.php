<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\LogiAuditLogResource\Widgets;

use AuroraWebSoftware\FilamentAstart\Traits\HasLogiAuditIntegration;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LogiAuditLogStatsWidget extends StatsOverviewWidget
{
    use HasLogiAuditIntegration;

    protected function getStats(): array
    {
        if (! static::hasLogiAudit()) {
            return [];
        }

        $model = static::getLogiAuditLogModel();

        $totalLogs = $model::count();

        $errorsToday = $model::whereIn('level', ['error', 'critical', 'alert', 'emergency'])
            ->where('logged_at', '>=', now()->subDay())
            ->count();

        $warningsToday = $model::where('level', 'warning')
            ->where('logged_at', '>=', now()->subDay())
            ->count();

        $infoToday = $model::where('level', 'info')
            ->where('logged_at', '>=', now()->subDay())
            ->count();

        return [
            Stat::make(
                __('filament-astart::filament-astart.resources.logiaudit_log.stats.total'),
                number_format($totalLogs)
            )
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5])
                ->description(__('filament-astart::filament-astart.resources.logiaudit_log.stats.total_desc'))
                ->descriptionIcon('heroicon-m-document-text')
                ->extraAttributes(['class' => 'astart-stat-primary']),

            Stat::make(
                __('filament-astart::filament-astart.resources.logiaudit_log.stats.errors_24h'),
                number_format($errorsToday)
            )
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->chart([2, 4, 1, 5, 3, 2, 1])
                ->description($errorsToday > 0
                    ? __('filament-astart::filament-astart.resources.logiaudit_log.stats.errors_active')
                    : __('filament-astart::filament-astart.resources.logiaudit_log.stats.errors_clear'))
                ->descriptionIcon($errorsToday > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-check-circle')
                ->extraAttributes(['class' => 'astart-stat-danger']),

            Stat::make(
                __('filament-astart::filament-astart.resources.logiaudit_log.stats.warnings_24h'),
                number_format($warningsToday)
            )
                ->icon('heroicon-o-exclamation-circle')
                ->color('warning')
                ->chart([3, 2, 5, 1, 4, 2, 3])
                ->description($warningsToday > 0
                    ? __('filament-astart::filament-astart.resources.logiaudit_log.stats.warnings_active')
                    : __('filament-astart::filament-astart.resources.logiaudit_log.stats.warnings_clear'))
                ->descriptionIcon($warningsToday > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-check-circle')
                ->extraAttributes(['class' => 'astart-stat-warning']),

            Stat::make(
                __('filament-astart::filament-astart.resources.logiaudit_log.stats.info_24h'),
                number_format($infoToday)
            )
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->chart([4, 6, 3, 7, 5, 4, 6])
                ->description(__('filament-astart::filament-astart.resources.logiaudit_log.stats.info_desc'))
                ->descriptionIcon('heroicon-m-information-circle')
                ->extraAttributes(['class' => 'astart-stat-info']),
        ];
    }
}
