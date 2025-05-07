<?php

namespace AuroraWebSoftware\FilamentAstart\Utils;

use AuroraWebSoftware\AAuth\Facades\AAuth;

class AAuthUtil
{
    public static function can(string $permission): bool
    {
        try {
            return AAuth::can($permission);
        } catch (\Exception $e) {
            redirect()->route('filament.admin.pages.role-switch');
        }

        return true;
    }
}
