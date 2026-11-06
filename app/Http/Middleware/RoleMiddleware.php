<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu untuk mengakses sistem.');
        }

        $userRole = Auth::user()->role?->role_name;

        if (!in_array($userRole, $roles)) {
            return redirect()->route('dashboard')->with('error', 'Akses ditolak. Peran Anda tidak memiliki izin untuk halaman tersebut.');
        }

        return $next($request);
    }
}
