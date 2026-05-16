<?php

namespace AuroraWebSoftware\FilamentAstart\Scopes;

use AuroraWebSoftware\AAuth\Facades\AAuth;
use AuroraWebSoftware\AAuth\Scopes\AAuthABACModelScope;
use AuroraWebSoftware\FilamentAstart\Utils\AAuthUtil;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Throwable;

/**
 * Wrapper around aauth's AAuthABACModelScope that adds a super-admin
 * bypass: when the current user is flagged as super-admin (via
 * config('aauth-advanced.super_admin')), the global ABAC filter is
 * skipped and the query returns unfiltered results.
 *
 * Outside an authenticated context (CLI, queue jobs without auth, etc.)
 * the bypass cannot be evaluated and the underlying scope still runs,
 * which is the safer default.
 */
class AStartAbacModelScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($this->shouldBypass()) {
            return;
        }

        (new AAuthABACModelScope)->apply($builder, $model);
    }

    private function shouldBypass(): bool
    {
        try {
            if (AAuth::isSuperAdmin()) {
                return true;
            }
        } catch (Throwable) {
            // aauth context is not initialised (no role selected, no auth).
            // Fall back to the Filament-aware helper which short-circuits
            // gracefully when there is no authenticated user.
        }

        return AAuthUtil::isSuperAdmin();
    }
}
