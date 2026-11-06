<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::with('role')->where('username', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kredensial tidak valid. Username atau password salah.',
            ], 401);
        }

        // Pembatasan perangkat mobile: Admin tidak boleh akses mobile, hanya PIC, Guru, dan Siswa
        if ($user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses aplikasi mobile hanya diperuntukkan bagi PIC, Guru, dan Siswa. Akun Admin / Waka Sarpras wajib menggunakan browser desktop/komputer.',
            ], 403);
        }

        $deviceName = $request->input('device_name', 'flutter_mobile_app');
        $plainToken = $user->createToken($deviceName);

        return response()->json([
            'success' => true,
            'message' => 'Autentikasi berhasil. Selamat datang di RAPI-Sekolah.',
            'token' => $plainToken,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'fullname' => $user->fullname,
                'role' => $user->role?->role_name,
                'role_label' => $user->role?->display_name ?? ucfirst($user->role?->role_name ?? ''),
                'phone_number' => $user->phone_number,
                'whatsapp_number' => $user->whatsapp_number,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth()->user();
        $user->load('role');

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'fullname' => $user->fullname,
                'role' => $user->role?->role_name,
                'role_label' => $user->role?->display_name ?? ucfirst($user->role?->role_name ?? ''),
                'phone_number' => $user->phone_number,
                'whatsapp_number' => $user->whatsapp_number,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $apiToken = $request->attributes->get('api_token');
        if ($apiToken) {
            $apiToken->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil. Sesi token telah dicabut.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'phone_number' => ['required', 'string'],
        ]);

        $username = trim($request->username);
        $inputPhone = preg_replace('/[^0-9]/', '', (string) $request->phone_number);

        $user = User::where('username', $username)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Akun dengan username tersebut tidak ditemukan.',
            ], 404);
        }

        $userPhone = preg_replace('/[^0-9]/', '', (string) $user->phone_number);
        $inputEnd = substr($inputPhone, -8);
        $userEnd = substr($userPhone, -8);

        if (empty($inputEnd) || $inputEnd !== $userEnd) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor telepon tidak cocok dengan data akun terdaftar.',
            ], 422);
        }

        $plainToken = \Illuminate\Support\Str::random(60);
        $hashedToken = hash('sha256', $plainToken);

        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
            ['username' => $user->username],
            [
                'token' => $hashedToken,
                'created_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Token pemulihan password berhasil diterbitkan.',
            'reset_token' => $plainToken,
            'username' => $user->username,
            'expires_in_minutes' => 15,
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $username = $request->username;
        $token = $request->token;

        $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('username', $username)->first();

        if (! $record || ! hash_equals($record->token, hash('sha256', $token))) {
            return response()->json([
                'success' => false,
                'message' => 'Token reset password tidak valid atau sudah tidak berlaku.',
            ], 422);
        }

        if (\Carbon\Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('username', $username)->delete();

            return response()->json([
                'success' => false,
                'message' => 'Sesi pemulihan password telah kadaluarsa.',
            ], 422);
        }

        $user = User::where('username', $username)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('username', $username)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui. Silakan login kembali dengan password baru.',
        ]);
    }
}
