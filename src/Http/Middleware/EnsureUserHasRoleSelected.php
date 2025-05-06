<?php


namespace AuroraWebSoftware\FilamentAstart\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use AuroraWebSoftware\AAuth\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRoleSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Giriş yapılmamışsa → hiç kontrol etme
        if (! Auth::check()) {
            return $next($request);
        }

        // 2. Şu sayfalarda kontrol etme
        if (
            $request->routeIs('filament.admin.pages.role-switch') ||
            $request->routeIs('filament.admin.auth.login') ||
            $request->routeIs('filament.admin.auth.*')
        ) {
            return $next($request);
        }

        // 3. Geçerli rolId varsa
        if ($request->session()->has('roleId')) {
            $role = Role::where('uro.user_id', Auth::id())
                ->where('uro.role_id', $request->session()->get('roleId'))
                ->leftJoin('user_role_organization_node as uro', 'uro.role_id', '=', 'roles.id')
                ->first();

            if ($role) {
                return $next($request);
            }
        }

        // 4. Rol yoksa → role seçme sayfasına at
        return redirect()->route('filament.admin.pages.role-switch');
    }

}
