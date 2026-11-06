<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    protected function tearDown(): void
    {
        // Bersihkan token pemulihan
        DB::table('password_reset_tokens')->where('username', 'like', 'test_%')->delete();
        DB::table('password_reset_tokens')->where('username', 'guru_andi')->delete();

        parent::tearDown();
    }

    public function test_halaman_lupa_password_dapat_diakses(): void
    {
        $response = $this->get('/lupa-password');

        $response->assertStatus(200);
        $response->assertSeeText('Lupa Kata Sandi?');
        $response->assertSeeText('Nomor Telepon Terdaftar');
    }

    public function test_permohonan_lupa_password_gagal_jika_username_tidak_ditemukan(): void
    {
        $response = $this->post('/lupa-password', [
            'username' => 'username_yang_tidak_ada_xyz',
            'phone_number' => '081234567890',
        ]);

        $response->assertSessionHasErrors('username');
    }

    public function test_permohonan_lupa_password_gagal_jika_nomor_telepon_salah(): void
    {
        $response = $this->post('/lupa-password', [
            'username' => 'guru_andi',
            'phone_number' => '089999999999', // Nomor salah
        ]);

        $response->assertSessionHasErrors('phone_number');
    }

    public function test_permohonan_lupa_password_berhasil_dan_membuat_token_pemulihan(): void
    {
        $user = User::where('username', 'guru_andi')->first();

        $response = $this->post('/lupa-password', [
            'username' => 'guru_andi',
            'phone_number' => $user->phone_number,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('password_reset_tokens', [
            'username' => 'guru_andi',
        ]);
    }

    public function test_halaman_reset_password_dapat_diakses_dengan_token_valid(): void
    {
        $plainToken = 'plain_test_token_1234567890';
        DB::table('password_reset_tokens')->updateOrInsert(
            ['username' => 'guru_andi'],
            [
                'token' => hash('sha256', $plainToken),
                'created_at' => now(),
            ]
        );

        $response = $this->get('/reset-password/' . $plainToken . '?username=guru_andi');

        $response->assertStatus(200);
        $response->assertSeeText('Atur Kata Sandi Baru');
        $response->assertSeeText('guru_andi');
        $response->assertSee('togglePasswordVisibility');
        $response->assertSee('Lihat konfirmasi kata sandi');
    }

    public function test_halaman_reset_password_menolak_token_palsu(): void
    {
        $response = $this->get('/reset-password/token_palsu_tidak_terdaftar?username=guru_andi');

        $response->assertRedirect('/lupa-password');
        $response->assertSessionHasErrors('username');
    }

    public function test_halaman_reset_password_menolak_token_kadaluarsa(): void
    {
        $plainToken = 'expired_test_token_12345';
        DB::table('password_reset_tokens')->updateOrInsert(
            ['username' => 'guru_andi'],
            [
                'token' => hash('sha256', $plainToken),
                'created_at' => Carbon::now()->subMinutes(20), // 20 menit yang lalu (kadaluarsa)
            ]
        );

        $response = $this->get('/reset-password/' . $plainToken . '?username=guru_andi');

        $response->assertRedirect('/lupa-password');
        $response->assertSessionHasErrors('username');
    }

    public function test_sukses_reset_password_dan_dapat_login_dengan_password_baru(): void
    {
        $plainToken = 'valid_reset_token_67890';
        DB::table('password_reset_tokens')->updateOrInsert(
            ['username' => 'guru_andi'],
            [
                'token' => hash('sha256', $plainToken),
                'created_at' => now(),
            ]
        );

        $response = $this->post('/reset-password', [
            'username' => 'guru_andi',
            'token' => $plainToken,
            'password' => 'passwordBaru2026!',
            'password_confirmation' => 'passwordBaru2026!',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status');

        // Pastikan password baru di-hash dan user bisa login
        $user = User::where('username', 'guru_andi')->first();
        $this->assertTrue(Hash::check('passwordBaru2026!', $user->password));

        // Kembalikan password demo awal agar test lain tidak terpengaruh
        $user->update(['password' => Hash::make('password123')]);
    }

    public function test_token_reset_dihapus_setelah_sukses_digunakan_mencegah_reuse(): void
    {
        $plainToken = 'one_time_token_12345';
        DB::table('password_reset_tokens')->updateOrInsert(
            ['username' => 'guru_andi'],
            [
                'token' => hash('sha256', $plainToken),
                'created_at' => now(),
            ]
        );

        $this->post('/reset-password', [
            'username' => 'guru_andi',
            'token' => $plainToken,
            'password' => 'passwordBaru123!',
            'password_confirmation' => 'passwordBaru123!',
        ]);

        // Token harus sudah terhapus
        $this->assertDatabaseMissing('password_reset_tokens', [
            'username' => 'guru_andi',
        ]);

        // Kembalikan password demo awal
        User::where('username', 'guru_andi')->update(['password' => Hash::make('password123')]);
    }

    public function test_api_forgot_password_dan_reset_password_untuk_mobile_flutter(): void
    {
        $user = User::where('username', 'guru_andi')->first();

        // 1. Request token via API
        $resForgot = $this->postJson('/api/forgot-password', [
            'username' => 'guru_andi',
            'phone_number' => $user->phone_number,
        ]);

        $resForgot->assertStatus(200);
        $resForgot->assertJson([
            'success' => true,
        ]);
        $resetToken = $resForgot->json('reset_token');
        $this->assertNotEmpty($resetToken);

        // 2. Reset password via API
        $resReset = $this->postJson('/api/reset-password', [
            'username' => 'guru_andi',
            'token' => $resetToken,
            'password' => 'passwordMobile2026!',
            'password_confirmation' => 'passwordMobile2026!',
        ]);

        $resReset->assertStatus(200);
        $resReset->assertJson([
            'success' => true,
        ]);

        // Verifikasi password baru bisa login via API
        $resLogin = $this->postJson('/api/login', [
            'username' => 'guru_andi',
            'password' => 'passwordMobile2026!',
        ]);
        $resLogin->assertStatus(200);

        // Kembalikan password demo awal
        $user->update(['password' => Hash::make('password123')]);
    }

    public function test_permohonan_lupa_password_mengirimkan_email_berisi_otp(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->post('/lupa-password', [
            'username' => 'phorus_admin',
        ]);

        $response->assertRedirect();
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ResetPasswordOtpMail::class, function ($mail) {
            return $mail->hasTo('christophorus420@gmail.com') && strlen($mail->otp) === 6;
        });

        $this->assertDatabaseHas('password_reset_tokens', [
            'username' => 'phorus_admin',
        ]);

        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('username', 'phorus_admin')->delete();
    }

    public function test_reset_password_berhasil_menggunakan_kode_otp_valid(): void
    {
        $otp = '582914';
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
            ['username' => 'phorus_admin'],
            [
                'email' => 'christophorus420@gmail.com',
                'token' => hash('sha256', $otp),
                'created_at' => now(),
            ]
        );

        $response = $this->post('/reset-password', [
            'username' => 'phorus_admin',
            'otp' => $otp,
            'password' => 'PasswordBaruPhorus#2026',
            'password_confirmation' => 'PasswordBaruPhorus#2026',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status');

        $user = User::where('username', 'phorus_admin')->first();
        $this->assertTrue(Hash::check('PasswordBaruPhorus#2026', $user->password));

        // Kembalikan password akun phorus_admin ke admin123
        $user->update(['password' => Hash::make('admin123')]);
    }

    public function test_reset_password_gagal_jika_kode_otp_salah(): void
    {
        $otp = '582914';
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
            ['username' => 'phorus_admin'],
            [
                'email' => 'christophorus420@gmail.com',
                'token' => hash('sha256', $otp),
                'created_at' => now(),
            ]
        );

        $response = $this->post('/reset-password', [
            'username' => 'phorus_admin',
            'otp' => '111222', // OTP salah
            'password' => 'PasswordBaruPhorus#2026',
            'password_confirmation' => 'PasswordBaruPhorus#2026',
        ]);

        $response->assertSessionHasErrors('otp');

        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('username', 'phorus_admin')->delete();
    }
}
