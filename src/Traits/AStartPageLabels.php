<?php

namespace AuroraWebSoftware\FilamentAstart\Traits;

/**
 * Trait for resource page labels from language file.
 *
 * Classes using this trait MUST define these static properties:
 * - protected static ?string $resourceKey = 'user'; // e.g., 'user', 'role', 'organization_node'
 * - protected static ?string $pageType = 'list'; // e.g., 'list', 'create', 'edit', 'view'
 */
trait AStartPageLabels
{
    public function getTitle(): string
    {
        $key = static::$resourceKey;
        $page = static::$pageType;

        if ($key && $page) {
            $translation = __("filament-astart::filament-astart.resources.{$key}.pages.{$page}.title");
            if ($translation !== "filament-astart::filament-astart.resources.{$key}.pages.{$page}.title") {
                return $translation;
            }
        }

        return parent::getTitle();
    }

    public function getHeading(): string
    {
        $key = static::$resourceKey;
        $page = static::$pageType;

        if ($key && $page) {
            $translation = __("filament-astart::filament-astart.resources.{$key}.pages.{$page}.heading");
            if ($translation !== "filament-astart::filament-astart.resources.{$key}.pages.{$page}.heading") {
                return $translation;
            }
        }

        return parent::getHeading();
    }

    public function getSubheading(): ?string
    {
        $key = static::$resourceKey;
        $page = static::$pageType;

        if ($key && $page) {
            $translation = __("filament-astart::filament-astart.resources.{$key}.pages.{$page}.subheading");
            if ($translation !== "filament-astart::filament-astart.resources.{$key}.pages.{$page}.subheading" && $translation !== null) {
                return $translation;
            }
        }

        return parent::getSubheading();
    }

    public function getBreadcrumb(): string
    {
        $key = static::$resourceKey;
        $page = static::$pageType;

        if ($key && $page) {
            $translation = __("filament-astart::filament-astart.resources.{$key}.pages.{$page}.breadcrumb");
            if ($translation !== "filament-astart::filament-astart.resources.{$key}.pages.{$page}.breadcrumb") {
                return $translation;
            }
        }

        return parent::getBreadcrumb();
    }
}
