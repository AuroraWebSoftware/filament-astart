<?php

namespace AuroraWebSoftware\FilamentAstart\Traits;

use AuroraWebSoftware\AAuth\Facades\AAuth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait AStartResourceAccessPolicy
{
    public static function parseFilamentResourceName(string $class): string
    {
        $classBase = class_basename($class);
        $modelName = str_replace('Resource', '', $classBase);

        return Str::snake($modelName);
    }

    protected static function getPermissionSlug(?string $action = null): string
    {
        $parsed = static::parseFilamentResourceName(static::class);

        return $action ? "{$parsed}_{$action}" : $parsed;

    }

    public static function canViewAny(): bool
    {
        return AAuth::can(static::getPermissionSlug('view_any'));
    }

    public static function canCreate(): bool
    {
        return AAuth::can(static::getPermissionSlug('create'));
    }

    public static function canEdit(Model $record): bool
    {
        return AAuth::can(static::getPermissionSlug('edit'), $record);
    }

    public static function canDelete(Model $record): bool
    {
        return AAuth::can(static::getPermissionSlug('delete'), $record);
    }

    public static function canDeleteAny(): bool
    {
        return AAuth::can(static::getPermissionSlug('delete_any'));
    }

    public static function canForceDelete(Model $record): bool
    {
        return AAuth::can(static::getPermissionSlug('force_delete'), $record);
    }

    public static function canForceDeleteAny(): bool
    {
        return AAuth::can(static::getPermissionSlug('force_delete_any'));
    }

    public static function canReorder(): bool
    {
        return AAuth::can(static::getPermissionSlug('reorder'));
    }

    public static function canReplicate(Model $record): bool
    {
        return AAuth::can(static::getPermissionSlug('replicate'), $record);
    }

    public static function canRestore(Model $record): bool
    {
        return AAuth::can(static::getPermissionSlug('restore'), $record);
    }

    public static function canRestoreAny(): bool
    {
        return AAuth::can(static::getPermissionSlug('restore_any'));
    }

    public static function canView(Model $record): bool
    {
        return AAuth::can(static::getPermissionSlug('view'), $record);
    }
}
