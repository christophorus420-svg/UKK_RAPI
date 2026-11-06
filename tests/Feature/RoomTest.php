<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Tests\TestCase;

class RoomTest extends TestCase
{
    protected User $admin;
    protected User $picBudi;
    protected User $picSiti;
    protected User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'admin')->first();
        $this->picBudi = User::where('username', 'pic_budi')->first();
        $this->picSiti = User::where('username', 'pic_siti')->first();
        $this->guru = User::where('username', 'guru_andi')->first();

        Room::where('room_name', 'like', 'Studio Podcast%')->delete();
    }

    protected function tearDown(): void
    {
        Room::where('room_name', 'like', 'Studio Podcast%')->delete();
        parent::tearDown();
    }

    public function test_user_terautentikasi_dapat_melihat_daftar_ruangan(): void
    {
        $response = $this->actingAs($this->guru)->get('/ruangan');
        $response->assertStatus(200);
        $response->assertSee('Daftar Ruangan');
        $response->assertSee('Lab Komputer RPL 1');
    }

    public function test_admin_dapat_mengakses_halaman_tambah_ruangan(): void
    {
        $response = $this->actingAs($this->admin)->get('/ruangan/tambah');
        $response->assertStatus(200);
        $response->assertSee('Tambah Ruangan');
    }

    public function test_guru_dilarang_mengakses_halaman_tambah_ruangan(): void
    {
        $response = $this->actingAs($this->guru)->get('/ruangan/tambah');
        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('error');
    }

    public function test_admin_dapat_menambah_ruangan_baru(): void
    {
        $response = $this->actingAs($this->admin)->post('/ruangan', [
            'room_name' => 'Studio Podcast & Multimedia',
            'capacity' => 20,
            'location' => 'Gedung Multimedia Lantai 3',
            'pic_id' => $this->picBudi->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/ruangan');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rooms', [
            'room_name' => 'Studio Podcast & Multimedia',
            'capacity' => 20,
            'pic_id' => $this->picBudi->id,
            'is_active' => true,
        ]);
    }

    public function test_validasi_gagal_jika_kapasitas_kurang_dari_satu(): void
    {
        $response = $this->actingAs($this->admin)->post('/ruangan', [
            'room_name' => 'Ruang Server Khusus',
            'capacity' => 0,
            'location' => 'Gedung B Lantai 1',
            'pic_id' => $this->picBudi->id,
        ]);

        $response->assertSessionHasErrors('capacity');
    }

    public function test_admin_dapat_mengubah_ruangan_dan_memutasi_pic(): void
    {
        $room = Room::where('room_name', 'Studio Podcast & Multimedia')->first()
            ?? Room::create([
                'room_name' => 'Studio Podcast & Multimedia',
                'capacity' => 20,
                'location' => 'Gedung Multimedia Lantai 3',
                'pic_id' => $this->picBudi->id,
                'is_active' => true,
            ]);

        $response = $this->actingAs($this->admin)->put("/ruangan/{$room->id}", [
            'room_name' => 'Studio Podcast & Broadcasting',
            'capacity' => 25,
            'location' => 'Gedung Multimedia Lantai 3 (Ruang 301)',
            'pic_id' => $this->picSiti->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/ruangan');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'room_name' => 'Studio Podcast & Broadcasting',
            'capacity' => 25,
            'pic_id' => $this->picSiti->id,
        ]);
    }

    public function test_admin_dapat_toggle_status_ketersediaan_ruangan(): void
    {
        $room = Room::where('room_name', 'Lab Komputer RPL 1')->first();
        $statusAwal = $room->is_active;

        $response = $this->actingAs($this->admin)->patch("/ruangan/{$room->id}/toggle");
        $response->assertSessionHas('success');

        $room->refresh();
        $this->assertEquals(!$statusAwal, $room->is_active);

        // Kembalikan ke status aktif semula
        $this->actingAs($this->admin)->patch("/ruangan/{$room->id}/toggle");
    }

    public function test_ruangan_dengan_riwayat_agenda_tidak_dapat_dihapus_permanen(): void
    {
        $room = Room::where('room_name', 'Lab Komputer RPL 1')->first();

        $response = $this->actingAs($this->admin)->delete("/ruangan/{$room->id}");
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
        ]);
    }
}
