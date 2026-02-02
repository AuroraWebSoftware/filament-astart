<?php

namespace AuroraWebSoftware\FilamentAstart\Http\Middleware;

use AuroraWebSoftware\AAuth\Models\Role;
use AuroraWebSoftware\FilamentAstart\Utils\AAuthUtil;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRoleSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        // Super admin kontrolü
        if (AAuthUtil::isSuperAdmin()) {
            return $next($request);
        }

        // Livewire istekleri ve account sayfaları
        if (
            $request->routeIs('filament.*.account*')
            || str_contains($request->path(), 'account')
            || $request->header('X-Livewire') !== null
        ) {
            return $next($request);
        }

        // Panel ID'sini al (dinamik route için)
        $panelId = Filament::getCurrentPanel()?->getId() ?? 'admin';

        // İzin verilen route'lar
        if (
            $request->routeIs("filament.{$panelId}.pages.role-switch")
            || $request->routeIs("filament.{$panelId}.auth.*")
            || $request->routeIs("filament.{$panelId}.pages.filogin-*")
            || $request->routeIs("filament.{$panelId}.pages.change-password")
            || $request->routeIs("filament.{$panelId}.password-reset.*")
            || $request->routeIs('fortify.*')
            || $request->routeIs('login')
            || $request->routeIs('two-factor.*')
            || $request->routeIs('password.*')
            || $request->routeIs('register')
            || str_contains($request->path(), 'filogin')
            || str_contains($request->path(), 'change-password')
            || str_contains($request->path(), 'password-reset')
        ) {
            return $next($request);
        }

        // Rol seçili mi kontrol et
        if ($request->session()->has('roleId')) {
            $user = Filament::auth()->user();

            $role = Role::query()
                ->leftJoin('user_role_organization_node as uro', 'uro.role_id', '=', 'roles.id')
                ->where('uro.user_id', $user?->getAuthIdentifier())
                ->where('uro.role_id', $request->session()->get('roleId'))
                ->first();

            if ($role) {
                return $next($request);
            }
        }

        return redirect()->route("filament.{$panelId}.pages.role-switch");
    }
}
