<?php

namespace AuroraWebSoftware\FilamentAstart\Http\Middleware;

use AuroraWebSoftware\AAuth\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRoleSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->routeIs('filament.admin.pages.role-switch') ||
            $request->routeIs('filament.admin.auth.login') ||
            $request->routeIs('filament.admin.auth.*') ||
            $request->routeIs('filament.*.account*') ||
            $request->routeIs('fortify.*') ||
            $request->routeIs('login') ||
            $request->routeIs('two-factor.*') ||
            $request->routeIs('password.*') ||
            $request->routeIs('register')
        ) {
            return $next($request);
        }

        if ($request->session()->has('roleId')) {
            $role = Role::where('uro.user_id', Auth::id())
                ->where('uro.role_id', $request->session()->get('roleId'))
                ->leftJoin('user_role_organization_node as uro', 'uro.role_id', '=', 'roles.id')
                ->first();

            if ($role) {
                return $next($request);
            }
        }

        return redirect()->route('filament.admin.pages.role-switch');
    }
}
