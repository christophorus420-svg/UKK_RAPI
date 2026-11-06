<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RestrictAdminOnMobile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Kolom username wajib diisi.',
            'password.required' => 'Kolom kata sandi wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // Pembatasan perangkat mobile: Admin hanya boleh desktop, mobile hanya untuk PIC, Guru, dan Siswa
            if ($user->isAdmin() && RestrictAdminOnMobile::isMobileDevice($request)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withInput($request->only('username', 'remember'))
                    ->withErrors([
                        'username' => 'Akses Akun Waka / Admin dibatasi hanya untuk perangkat Komputer / Laptop. Akses mobile hanya diperuntukkan bagi PIC, Guru, dan Siswa.',
                    ]);
            }

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang, ' . $user->fullname . ' (' . strtoupper($user->role->role_name) . ')!');
        }

        return back()
            ->withInput($request->only('username', 'remember'))
            ->withErrors([
                'username' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
            ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('info', 'Anda telah berhasil keluar dari sistem.');
    }
}
