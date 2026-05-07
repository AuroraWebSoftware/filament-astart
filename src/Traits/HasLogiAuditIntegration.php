<?php

namespace AuroraWebSoftware\FilamentAstart\Traits;

trait HasLogiAuditIntegration
{
    public static function hasLogiAudit(): bool
    {
        return class_exists(\AuroraWebSoftware\LogiAudit\Models\LogiAuditLog::class);
    }

    public static function getLogiAuditLogModel(): ?string
    {
        if (! static::hasLogiAudit()) {
            return null;
        }

        return \AuroraWebSoftware\LogiAudit\Models\LogiAuditLog::class;
    }

    public static function getLogiAuditHistoryModel(): ?string
    {
        if (! static::hasLogiAudit()) {
            return null;
        }

        return \AuroraWebSoftware\LogiAudit\Models\LogiAuditHistory::class;
    }
}
