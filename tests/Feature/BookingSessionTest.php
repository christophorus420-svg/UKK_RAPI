<?php

namespace Tests\Feature;

use App\Models\BookingSession;
use App\Models\User;
use Tests\TestCase;

class BookingSessionTest extends TestCase
{
    protected User $admin;
    protected User $guru;
    protected User $pic;
    protected BookingSession $sesi1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'admin')->first();
        $this->guru = User::where('username', 'guru_andi')->first();
        $this->pic = User::where('username', 'pic_budi')->first();

        $this->sesi1 = BookingSession::orderBy('id')->first();
    }

    protected function tearDown(): void
    {
        // Kembalikan waktu sesi 1 ke default setelah pengujian
        BookingSession::where('id', 1)->update([
            'session_name' => 'Sesi 1 (Jam Ke-1)',
            'start_time' => '07:00:00',
            'end_time' => '07:45:00',
            'is_active' => true,
        ]);

        parent::tearDown();
    }

    public function test_admin_dapat_melihat_pengaturan_sesi_di_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Waktu Sesi Peminjaman');
        $response->assertSee('Sesi 1 (Jam Ke-1)');
        $response->assertSee('Edit Waktu');
        $response->assertSee('modal-edit-sesi');
    }

    public function test_guru_dan_pic_tidak_melihat_pengaturan_sesi_di_dashboard(): void
    {
        $responseGuru = $this->actingAs($this->guru)->get('/dashboard');
        $responseGuru->assertStatus(200);
        $responseGuru->assertDontSee('Pengaturan Waktu Sesi Peminjaman');
        $responseGuru->assertDontSee('modal-edit-sesi');

        $responsePic = $this->actingAs($this->pic)->get('/dashboard');
        $responsePic->assertStatus(200);
        $responsePic->assertDontSee('Pengaturan Waktu Sesi Peminjaman');
        $responsePic->assertDontSee('modal-edit-sesi');
    }

    public function test_admin_dapat_mengubah_nama_dan_waktu_sesi(): void
    {
        $response = $this->actingAs($this->admin)->put("/sesi/{$this->sesi1->id}", [
            'session_name' => 'Sesi 1 (Pagi Efektif)',
            'start_time' => '07:00',
            'end_time' => '07:45',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success');

        $this->sesi1->refresh();
        $this->assertEquals('Sesi 1 (Pagi Efektif)', $this->sesi1->session_name);
        $this->assertStringStartsWith('07:00', $this->sesi1->start_time);
        $this->assertStringStartsWith('07:45', $this->sesi1->end_time);
        $this->assertTrue($this->sesi1->is_active);
    }

    public function test_guru_dilarang_mengubah_waktu_sesi(): void
    {
        $response = $this->actingAs($this->guru)->put("/sesi/{$this->sesi1->id}", [
            'session_name' => 'Sesi Ilegal',
            'start_time' => '07:00',
            'end_time' => '07:45',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('error');

        $this->sesi1->refresh();
        $this->assertEquals('Sesi 1 (Jam Ke-1)', $this->sesi1->session_name);
    }

    public function test_validasi_menolak_jika_jam_selesai_lebih_awal_atau_sama_dengan_jam_mulai(): void
    {
        $response = $this->actingAs($this->admin)->put("/sesi/{$this->sesi1->id}", [
            'session_name' => 'Sesi 1 (Jam Ke-1)',
            'start_time' => '08:00',
            'end_time' => '07:00',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors(['end_time']);

        $this->sesi1->refresh();
        $this->assertStringStartsWith('07:00', $this->sesi1->start_time);
    }

    public function test_admin_dapat_toggle_status_aktif_sesi(): void
    {
        $this->assertTrue($this->sesi1->is_active);

        $response = $this->actingAs($this->admin)->patch("/sesi/{$this->sesi1->id}/toggle");

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success');

        $this->sesi1->refresh();
        $this->assertFalse($this->sesi1->is_active);

        // Toggle kembali
        $this->actingAs($this->admin)->patch("/sesi/{$this->sesi1->id}/toggle");
        $this->sesi1->refresh();
        $this->assertTrue($this->sesi1->is_active);
    }

    public function test_perubahan_waktu_sesi_langsung_terefleksi_di_form_peminjaman_guru(): void
    {
        // Ubah sesi 1 menjadi 07:15 - 08:00
        $this->actingAs($this->admin)->put("/sesi/{$this->sesi1->id}", [
            'session_name' => 'Sesi 1 (Pagi Khusus)',
            'start_time' => '07:15',
            'end_time' => '08:00',
            'is_active' => '1',
        ]);

        // Guru membuka formulir pengajuan peminjaman
        $response = $this->actingAs($this->guru)->get('/agenda/ajukan');

        $response->assertStatus(200);
        $response->assertSee('Sesi 1 (Pagi Khusus)');
        $response->assertSee('07:15 - 08:00 WIB');
    }
}
