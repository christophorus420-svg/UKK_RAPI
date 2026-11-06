<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SendResetTokenRequest;
use App\Mail\ResetPasswordOtpMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    /**
     * Menampilkan formulir permintaan lupa password
     */
    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Memvalidasi akun dan mengirimkan kode OTP reset password ke email terdaftar
     */
    public function sendResetToken(SendResetTokenRequest $request): RedirectResponse
    {
        $inputEmail = trim((string) $request->email);
        $inputUsername = trim((string) $request->username);
        $inputPhone = preg_replace('/[^0-9]/', '', (string) $request->phone_number);

        // Cari user berdasarkan email atau username
        $user = User::where(function ($q) use ($inputEmail, $inputUsername) {
            if (!empty($inputEmail)) {
                $q->where('email', $inputEmail);
            }
            if (!empty($inputUsername)) {
                $q->orWhere('username', $inputUsername);
            }
        })->first();

        // Verifikasi keberadaan akun
        if (! $user) {
            return back()
                ->withInput($request->all())
                ->withErrors([
                    'email' => 'Akun dengan email atau username tersebut tidak ditemukan dalam sistem.',
                    'username' => 'Akun dengan email atau username tersebut tidak ditemukan dalam sistem.',
                ]);
        }

        // Cek ketersediaan email akun
        if (empty($user->email)) {
            return back()
                ->withInput($request->all())
                ->withErrors([
                    'email' => 'Akun ini belum memiliki alamat email yang terdaftar. Hubungi administrator.',
                ]);
        }

        // Verifikasi nomor telepon jika diinput
        if (!empty($inputPhone)) {
            $userPhone = preg_replace('/[^0-9]/', '', (string) $user->phone_number);
            $inputEnd = substr($inputPhone, -8);
            $userEnd = substr($userPhone, -8);

            if (empty($inputEnd) || $inputEnd !== $userEnd) {
                return back()
                    ->withInput($request->all())
                    ->withErrors(['phone_number' => 'Nomor telepon tidak cocok dengan data akun terdaftar.']);
            }
        }

        // Hasilkan kode OTP 6-digit numerik acak
        $otp = sprintf('%06d', mt_rand(100000, 999999));
        $hashedToken = hash('sha256', $otp);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['username' => $user->username],
            [
                'email' => $user->email,
                'token' => $hashedToken,
                'created_at' => now(),
            ]
        );

        $resetUrl = route('password.reset.form', [
            'token' => $otp,
            'email' => $user->email,
            'username' => $user->username,
        ]);

        // Kirim email OTP
        try {
            Mail::to($user->email)->send(new ResetPasswordOtpMail($user, $otp, $resetUrl, 15));
        } catch (\Throwable $e) {
            Log::error("Gagal mengirim email reset password OTP ke {$user->email}: " . $e->getMessage());
        }

        // Masking alamat email untuk privasi tampilan
        $emailParts = explode('@', $user->email);
        $namePart = $emailParts[0];
        $maskedName = strlen($namePart) > 2 ? substr($namePart, 0, 2) . str_repeat('*', max(1, strlen($namePart) - 2)) : $namePart . '***';
        $maskedEmail = $maskedName . '@' . ($emailParts[1] ?? '');

        return redirect()->route('password.reset.form', [
            'token' => 'verify',
            'email' => $user->email,
            'username' => $user->username,
        ])->with([
            'status' => "Kode verifikasi OTP 6 digit telah dikirimkan ke email Anda ({$maskedEmail}). Silakan periksa kotak masuk atau spam email Anda.",
        ]);
    }

    /**
     * Menampilkan formulir verifikasi OTP dan pembuatan password baru
     */
    public function showResetForm(Request $request, ?string $token = null): View|RedirectResponse
    {
        $token = $token ?? $request->query('token');
        $username = $request->query('username');
        $email = $request->query('email');

        if (! $username && ! $email) {
            return redirect()->route('password.request')
                ->withErrors([
                    'email' => 'Permintaan tidak valid. Parameter identitas akun tidak ditemukan.',
                    'username' => 'Permintaan tidak valid. Parameter identitas akun tidak ditemukan.',
                ]);
        }

        $record = DB::table('password_reset_tokens')
            ->where(function ($q) use ($username, $email) {
                if ($username) {
                    $q->where('username', $username);
                }
                if ($email) {
                    $q->orWhere('email', $email);
                }
            })
            ->first();

        if (! $record) {
            return redirect()->route('password.request')
                ->withErrors([
                    'email' => 'Sesi pemulihan kata sandi tidak ditemukan atau sudah kadaluarsa. Silakan ajukan kembali.',
                    'username' => 'Sesi pemulihan kata sandi tidak ditemukan atau sudah kadaluarsa. Silakan ajukan kembali.',
                ]);
        }

        // Token kadaluarsa jika lebih dari 15 menit
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('username', $record->username)->delete();

            return redirect()->route('password.request')
                ->withErrors([
                    'email' => 'Sesi pemulihan password telah kadaluarsa (melebihi 15 menit). Silakan ajukan kembali.',
                    'username' => 'Sesi pemulihan password telah kadaluarsa (melebihi 15 menit). Silakan ajukan kembali.',
                ]);
        }

        // Jika token disertakan di URL (bukan default 'verify'), validasi kecocokannya
        $prefilledOtp = '';
        if ($token && $token !== 'verify') {
            if (hash_equals($record->token, hash('sha256', $token))) {
                if (preg_match('/^\d{6}$/', $token)) {
                    $prefilledOtp = $token;
                }
            } else {
                return redirect()->route('password.request')
                    ->withErrors([
                        'email' => 'Tautan pemulihan password tidak valid atau sudah tidak berlaku.',
                        'username' => 'Tautan pemulihan password tidak valid atau sudah tidak berlaku.',
                    ]);
            }
        }

        return view('auth.reset-password', [
            'token' => $token ?? 'verify',
            'prefilledOtp' => $prefilledOtp,
            'username' => $record->username ?? $username,
            'email' => $record->email ?? $email,
        ]);
    }

    /**
     * Menyimpan password baru setelah memvalidasi kode OTP
     */
    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $username = $request->username;
        $email = $request->email;
        $inputOtpOrToken = trim((string) ($request->otp ?: $request->token));

        $record = DB::table('password_reset_tokens')
            ->where(function ($q) use ($username, $email) {
                if ($username) {
                    $q->where('username', $username);
                }
                if ($email) {
                    $q->orWhere('email', $email);
                }
            })
            ->first();

        if (! $record) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Sesi pemulihan tidak valid atau sudah kadaluarsa. Silakan ajukan kembali.']);
        }

        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('username', $record->username)->delete();

            return redirect()->route('password.request')
                ->withErrors(['email' => 'Sesi pemulihan password telah kadaluarsa. Silakan ajukan kembali.']);
        }

        // Validasi kecocokan kode OTP / Token
        if (! hash_equals($record->token, hash('sha256', $inputOtpOrToken))) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['otp' => 'Kode OTP verifikasi salah atau tidak valid. Silakan periksa kembali email Anda.']);
        }

        $user = User::where('username', $record->username)
            ->orWhere(function ($q) use ($email) {
                if ($email) {
                    $q->where('email', $email);
                }
            })->first();

        if (! $user) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Pengguna tidak ditemukan dalam sistem.']);
        }

        // Perbarui password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Hapus token yang telah berhasil digunakan (mencegah replay attack)
        DB::table('password_reset_tokens')->where('username', $record->username)->delete();

        return redirect()->route('login')
            ->with('status', 'Password Anda berhasil diperbarui! Silakan masuk menggunakan password baru.');
    }
}
