<?php

namespace AuroraWebSoftware\FilamentAstart\Traits;

use AuroraWebSoftware\AAuth\Facades\AAuth;
use AuroraWebSoftware\FilamentAstart\Utils\AAuthUtil;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Support\Str;

trait AStartCustomPageAccessPolicy
{
    public static function isPageBelongsToResource(string $pageClass): bool
    {
        if (! class_exists($pageClass)) {
            return false;
        }

        $reflection = new \ReflectionClass($pageClass);

        if ($reflection->hasProperty('resource')) {
            $property = $reflection->getProperty('resource');
            $property->setAccessible(true);
            $value = $property->getValue();
            if (! empty($value) && is_subclass_of($value, Resource::class)) {
                return true;
            }
        }

        if ($reflection->hasMethod('getResource')) {
            $method = $reflection->getMethod('getResource');

            return $method->getDeclaringClass()->getName() !== Page::class;
        }

        return false;
    }

    protected static function getPermissionSlug(?string $action = null): string
    {
        $parsed = static::parseFilamentPageName(static::class);

        return $action ? "{$parsed}_{$action}" : $parsed;
    }

    public static function parseFilamentPageName(string $class): string
    {
        $classBase = class_basename($class);
        $modelName = str_replace('Pages', '', $classBase);

        return Str::snake($modelName);
    }

    public static function canAccess(array $parameters = []): bool
    {
        return AAuthUtil::can(static::getPermissionSlug(self::$permission));
    }
}
