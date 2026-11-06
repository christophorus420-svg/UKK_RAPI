<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Token autentikasi Bearer tidak ditemukan pada header request.',
            ], 401);
        }

        $hashedToken = hash('sha256', $token);
        $apiToken = ApiToken::with('user.role')->where('token', $hashedToken)->first();

        if (! $apiToken || ! $apiToken->user) {
            return response()->json([
                'success' => false,
                'message' => 'Token autentikasi tidak valid atau sudah tidak berlaku.',
            ], 401);
        }

        if ($apiToken->expires_at && $apiToken->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi login telah kadaluarsa. Silakan login kembali.',
            ], 401);
        }

        if ($apiToken->user?->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses API mobile tidak diizinkan untuk Admin / Waka Sarpras. Gunakan web browser komputer.',
            ], 403);
        }

        // Perbarui waktu terakhir token digunakan
        $apiToken->update(['last_used_at' => now()]);

        // Autentikasi user pada sesi request API ini
        Auth::setUser($apiToken->user);
        $request->attributes->set('api_token', $apiToken);

        return $next($request);
    }
}
