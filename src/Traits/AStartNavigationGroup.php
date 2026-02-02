<?php

namespace AuroraWebSoftware\FilamentAstart\Traits;

/**
 * Trait for dynamic navigation group from config + language file.
 *
 * Classes using this trait MUST define:
 * - protected static ?string $resourceKey = 'user'; // e.g., 'user', 'role', 'organization_node'
 */
trait AStartNavigationGroup
{
    public static function getNavigationGroup(): ?string
    {
        $resourceKey = static::$resourceKey ?? null;

        if (! $resourceKey) {
            return __('filament-astart::filament-astart.navigation_group');
        }

        $configKey = "filament-astart.resources.{$resourceKey}.navigation_group_key";
        $navigationGroupKey = config($configKey);

        if ($navigationGroupKey === null) {
            // Use default navigation group from lang file
            return __('filament-astart::filament-astart.navigation_group');
        }

        // Use custom navigation group key from lang file
        return __("filament-astart::filament-astart.navigation_groups.{$navigationGroupKey}");
    }
}
