<?php

namespace AuroraWebSoftware\FilamentAstart\Traits;

use AuroraWebSoftware\FilamentAstart\Scopes\AStartAbacModelScope;

/**
 * Drop-in replacement for AAuthABACModel that adds a super-admin
 * bypass on top of aauth's ABAC global scope.
 *
 * Usage: a model implementing AAuthABACModelInterface should `use`
 * this trait *instead of* AAuthABACModel. The bypass is delegated to
 * AStartAbacModelScope.
 */
trait AStartAbacModel
{
    public static function bootAStartAbacModel(): void
    {
        static::addGlobalScope(new AStartAbacModelScope);
    }
}
