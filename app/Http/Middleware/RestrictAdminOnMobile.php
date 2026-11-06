<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RestrictAdminOnMobile
{
    /**
     * Memeriksa apakah request berasal dari perangkat mobile (HP / Tablet)
     */
    public static function isMobileDevice(Request $request): bool
    {
        // Mendukung custom header / input (untuk pengujian otomatis dan aplikasi mobile)
        if ($request->header('X-Device-Type') === 'mobile' || $request->input('device_type') === 'mobile') {
            return true;
        }

        $userAgent = strtolower($request->header('User-Agent', ''));
        if (empty($userAgent)) {
            return false;
        }

        $mobileKeywords = [
            'mobile', 'android', 'iphone', 'ipod', 'ipad',
            'blackberry', 'opera mini', 'opera mobi', 'windows phone', 'iemobile', 'webos'
        ];

        foreach ($mobileKeywords as $keyword) {
            if (str_contains($userAgent, $keyword)) {
                return true;
            }
        }

        return false;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->isAdmin() && self::isMobileDevice($request)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses Admin / Waka Sarpras dibatasi hanya untuk perangkat Komputer / Laptop. Akses mobile hanya diperuntukkan bagi PIC, Guru, dan Siswa.',
                ], 403);
            }

            // Jika admin membuka sistem via browser HP, logout dan beri peringatan
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Akses Akun Waka / Admin dibatasi hanya untuk perangkat Komputer / Laptop. Akses mobile hanya diperuntukkan bagi PIC, Guru, dan Siswa.');
        }

        return $next($request);
    }
}
