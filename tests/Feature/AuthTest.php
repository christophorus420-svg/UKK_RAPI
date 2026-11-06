<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_halaman_login_dapat_diakses(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('RAPI-Sekolah');
        $response->assertSee('Masuk ke Akun');
    }

    public function test_user_dapat_login_dengan_kredensial_valid(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_user_gagal_login_dengan_password_salah(): void
    {
        $response = $this->from('/login')->post('/login', [
            'username' => 'admin',
            'password' => 'passwordsalah',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_dashboard_terproteksi_dan_menolak_tamu(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_user_terautentikasi_dapat_melihat_dashboard_dan_role(): void
    {
        $user = User::where('username', 'admin')->first();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee($user->fullname);
        $response->assertSee('Eskalasi Kerusakan ke PIC');
    }

    public function test_user_guru_melihat_bagian_khusus_guru_di_dashboard(): void
    {
        $guru = User::where('username', 'guru_andi')->first();

        $response = $this->actingAs($guru)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Agenda Peminjaman Saya');
        $response->assertSee('Laporan Kerusakan Saya');
    }

    public function test_user_dapat_logout(): void
    {
        $user = User::where('username', 'admin')->first();

        $response = $this->actingAs($user)->post('/logout');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_halaman_login_memiliki_fitur_toggle_lihat_password(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('togglePasswordVisibility');
        $response->assertSee('Lihat kata sandi');
        $response->assertSee('eye-open-icon');
        $response->assertSee('eye-closed-icon');
    }
}

