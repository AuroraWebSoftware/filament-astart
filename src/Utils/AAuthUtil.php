<?php

namespace AuroraWebSoftware\FilamentAstart\Utils;

use AuroraWebSoftware\AAuth\Facades\AAuth;
use Filament\Facades\Filament;

class AAuthUtil
{
    public static function can(string $permission): bool
    {
        if (self::isSuperAdmin()) {
            return true;
        }

        try {
            return AAuth::can($permission);
        } catch (\Exception $e) {
            $panelId = Filament::getCurrentPanel()?->getId() ?? 'admin';
            redirect()->route("filament.{$panelId}.pages.role-switch");
        }

        return true;
    }

    public static function isSuperAdmin(): bool
    {
        $config = config('aauth-advanced.super_admin', []);

        if (! ($config['enabled'] ?? false)) {
            return false;
        }

        $column = $config['column'] ?? 'is_super_admin';
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        return (bool) $user->{$column};
    }
}
