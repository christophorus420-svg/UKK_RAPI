<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class MobileAccessRestrictionTest extends TestCase
{
    protected User $admin;
    protected User $guru;
    protected User $pic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'admin')->first();
        $this->guru = User::where('username', 'guru_andi')->first();
        $this->pic = User::where('username', 'pic_budi')->first();
    }

    public function test_admin_dilarang_login_dari_perangkat_mobile_web(): void
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 13; SM-S908B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Mobile Safari/537.36',
        ])->post('/login', [
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['username']);
        $this->assertGuest();
    }

    public function test_guru_diizinkan_login_dari_perangkat_mobile_web(): void
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1',
        ])->post('/login', [
            'username' => 'guru_andi',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->guru);
    }

    public function test_pic_diizinkan_login_dari_perangkat_mobile_web(): void
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 12; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Mobile Safari/537.36',
        ])->post('/login', [
            'username' => 'pic_budi',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->pic);
    }

    public function test_siswa_dan_publik_dapat_mengakses_kalender_dari_mobile_tanpa_login(): void
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 10; Mobile) Mobile Safari/537.36',
        ])->get('/kalender-sekolah');

        $response->assertStatus(200);
        $response->assertSee('Kalender Publik');
    }

    public function test_admin_yang_membuka_dashboard_dari_mobile_akan_dikeluarkan_dan_diarahkan_ke_login(): void
    {
        $response = $this->actingAs($this->admin)->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 11; SM-A515F) Mobile Safari/537.36',
        ])->get('/dashboard');

        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_admin_dapat_login_dan_mengakses_dashboard_dari_perangkat_desktop(): void
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
        ])->post('/login', [
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->admin);

        $responseDashboard = $this->get('/dashboard');
        $responseDashboard->assertStatus(200);
        $responseDashboard->assertSee('Drs. H. Ahmad Fauzi, M.Pd');
    }

    public function test_admin_dilarang_login_melalui_api_mobile(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'password123',
            'device_name' => 'hp_android_admin',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('Admin', $response->json('message'));
    }

    public function test_guru_dan_pic_dapat_login_melalui_api_mobile(): void
    {
        $responseGuru = $this->postJson('/api/login', [
            'username' => 'guru_andi',
            'password' => 'password123',
            'device_name' => 'hp_flutter_guru',
        ]);

        $responseGuru->assertStatus(200);
        $this->assertTrue($responseGuru->json('success'));
        $this->assertNotEmpty($responseGuru->json('token'));

        $responsePic = $this->postJson('/api/login', [
            'username' => 'pic_budi',
            'password' => 'password123',
            'device_name' => 'hp_flutter_pic',
        ]);

        $responsePic->assertStatus(200);
        $this->assertTrue($responsePic->json('success'));
        $this->assertNotEmpty($responsePic->json('token'));
    }
}
