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

            return false;
        }
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

    /**
     * Whether the ABAC rule management feature is enabled.
     */
    public static function isAbacEnabled(): bool
    {
        return (bool) config('astart-auth.abac.enabled', false);
    }

    /**
     * Get all registered ABAC-enabled models keyed by model_type.
     *
     * @return array<string, array{class: class-string, label: string, attributes: array<string, array<string, mixed>>}>
     */
    public static function getAbacModels(): array
    {
        if (! self::isAbacEnabled()) {
            return [];
        }

        $models = config('astart-auth.abac.models', []);

        return is_array($models) ? $models : [];
    }

    /**
     * Get the human-readable label for a registered ABAC model_type.
     * Falls back to the model_type itself when no explicit label is set.
     */
    public static function getAbacModelLabel(string $modelType): string
    {
        $models = self::getAbacModels();

        return $models[$modelType]['label'] ?? $modelType;
    }

    /**
     * Get the attribute whitelist for a registered ABAC model_type.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getAbacAttributes(string $modelType): array
    {
        $models = self::getAbacModels();
        $attributes = $models[$modelType]['attributes'] ?? [];

        return is_array($attributes) ? $attributes : [];
    }

    /**
     * Get the configured options list for a specific attribute,
     * or null when the attribute is free-form.
     *
     * @return array<int, mixed>|null
     */
    public static function getAbacAttributeOptions(string $modelType, string $attribute): ?array
    {
        $attributes = self::getAbacAttributes($modelType);
        $options = $attributes[$attribute]['options'] ?? null;

        return is_array($options) ? array_values($options) : null;
    }
}
