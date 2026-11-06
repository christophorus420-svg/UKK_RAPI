<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class ReportTest extends TestCase
{
    protected User $admin;
    protected User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'admin')->first();
        $this->guru = User::where('username', 'guru_andi')->first();
    }

    public function test_user_non_admin_dilarang_mengakses_halaman_laporan(): void
    {
        $response = $this->actingAs($this->guru)->get('/laporan');
        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('error');
    }

    public function test_admin_dapat_mengakses_halaman_rekap_laporan(): void
    {
        $response = $this->actingAs($this->admin)->get('/laporan');
        $response->assertStatus(200);
        $response->assertSee('Rekap Laporan Peminjaman & Pengaduan');
        $response->assertSee('Unduh PDF');
        $response->assertSee('Unduh Excel (CSV)');
    }

    public function test_admin_dapat_filter_laporan_harian_dan_bulanan(): void
    {
        // Filter bulanan
        $responseMonthly = $this->actingAs($this->admin)->get('/laporan?period=monthly&month=10&year=2026');
        $responseMonthly->assertStatus(200);
        $responseMonthly->assertSee('Oktober 2026');

        // Filter harian
        $responseDaily = $this->actingAs($this->admin)->get('/laporan?period=daily&date=2026-10-22');
        $responseDaily->assertStatus(200);
        $responseDaily->assertSee('22 Oktober 2026');
    }

    public function test_admin_dapat_mengunduh_laporan_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get('/laporan/export-pdf?period=monthly&month=10&year=2026');
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_admin_dapat_mengunduh_laporan_csv(): void
    {
        $response = $this->actingAs($this->admin)->get('/laporan/export-csv?period=monthly&month=10&year=2026');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }
}
