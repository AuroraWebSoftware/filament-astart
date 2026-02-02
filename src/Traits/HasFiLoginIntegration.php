<?php

namespace AuroraWebSoftware\FilamentAstart\Traits;

/**
 * Trait to check FiLogin integration availability
 */
trait HasFiLoginIntegration
{
    /**
     * Check if FiLogin plugin is installed and available
     */
    public static function hasFiLogin(): bool
    {
        return class_exists(\AuroraWebSoftware\FiLogin\FiLoginPlugin::class);
    }

    /**
     * Get FiLogin user lockout model class if available
     */
    public static function getFiLoginUserLockoutModel(): ?string
    {
        if (! static::hasFiLogin()) {
            return null;
        }

        return \AuroraWebSoftware\FiLogin\Models\FiLoginUserLockout::class;
    }

    /**
     * Get FiLogin session model class if available
     */
    public static function getFiLoginSessionModel(): ?string
    {
        if (! static::hasFiLogin()) {
            return null;
        }

        return \AuroraWebSoftware\FiLogin\Models\FiLoginSession::class;
    }

    /**
     * Get FiLogin login attempt model class if available
     */
    public static function getFiLoginLoginAttemptModel(): ?string
    {
        if (! static::hasFiLogin()) {
            return null;
        }

        return \AuroraWebSoftware\FiLogin\Models\FiLoginLoginAttempt::class;
    }

    /**
     * Get FiLogin password policy model class if available
     */
    public static function getFiLoginPasswordPolicyModel(): ?string
    {
        if (! static::hasFiLogin()) {
            return null;
        }

        return \AuroraWebSoftware\FiLogin\Models\FiLoginPasswordPolicy::class;
    }

    /**
     * Get FiLogin known device model class if available
     */
    public static function getFiLoginKnownDeviceModel(): ?string
    {
        if (! static::hasFiLogin()) {
            return null;
        }

        return \AuroraWebSoftware\FiLogin\Models\FiLoginKnownDevice::class;
    }

    /**
     * Get FiLogin password history model class if available
     */
    public static function getFiLoginPasswordHistoryModel(): ?string
    {
        if (! static::hasFiLogin()) {
            return null;
        }

        return \AuroraWebSoftware\FiLogin\Models\FiLoginPasswordHistory::class;
    }
}
