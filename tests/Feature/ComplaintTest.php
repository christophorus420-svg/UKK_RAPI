<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintTest extends TestCase
{
    protected User $admin;
    protected User $picBudi; // PIC Lab 1, Lab 2, Bengkel IoT
    protected User $picSiti; // PIC Aula & Ruang Rapat
    protected User $guru;
    protected Room $labRpl1;
    protected Room $aula;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'admin')->first();
        $this->picBudi = User::where('username', 'pic_budi')->first();
        $this->picSiti = User::where('username', 'pic_siti')->first();
        $this->guru = User::where('username', 'guru_andi')->first();

        $this->labRpl1 = Room::where('room_name', 'Lab Komputer RPL 1')->first();
        $this->aula = Room::where('room_name', 'Aula Graha Wiyata')->first();
    }

    public function test_user_dapat_melihat_daftar_pengaduan_sarpras(): void
    {
        $response = $this->actingAs($this->guru)->get('/pengaduan');
        $response->assertStatus(200);
        $response->assertSee('Pengaduan Kerusakan Sarpras');
    }

    public function test_guru_dapat_membuat_laporan_kerusakan_sarpras(): void
    {
        $response = $this->actingAs($this->guru)->post('/pengaduan', [
            'room_id' => $this->labRpl1->id,
            'description' => 'Monitor PC nomor 08 bergaris-garis vertikal dan sering mati sendiri.',
        ]);

        $response->assertRedirect('/pengaduan');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('complaints', [
            'room_id' => $this->labRpl1->id,
            'reporter_id' => $this->guru->id,
            'status_repair' => 'Belum Diperbaiki',
            'handler_id' => $this->picBudi->id, // Otomatis didelegasikan ke PIC Lab 1
        ]);
    }

    public function test_guru_dapat_unggah_foto_bukti_fisik_kamera_dan_tersimpan_aman(): void
    {
        Storage::fake('public');

        $fakePhoto = UploadedFile::fake()->image('bukti_kamera.jpg', 1600, 1200);

        $response = $this->actingAs($this->guru)->post('/pengaduan', [
            'room_id' => $this->labRpl1->id,
            'description' => 'Engsel jendela kaca patah tertiup angin kencang kemarin sore.',
            'photo' => $fakePhoto,
        ]);

        $response->assertRedirect('/pengaduan');
        $response->assertSessionHas('success');

        $complaint = Complaint::where('description', 'like', 'Engsel jendela kaca%')->first();
        $this->assertNotNull($complaint);
        $this->assertNotNull($complaint->photo_path);

        // Bersihkan data uji
        $complaint->delete();
    }

    public function test_validasi_laporan_gagal_jika_deskripsi_kurang_dari_10_karakter(): void
    {
        $response = $this->actingAs($this->guru)->post('/pengaduan', [
            'room_id' => $this->labRpl1->id,
            'description' => 'Rusak',
        ]);

        $response->assertSessionHasErrors('description');
    }

    public function test_pic_ruangan_terkait_dapat_mengubah_status_siklus_perbaikan(): void
    {
        $complaint = Complaint::create([
            'room_id' => $this->labRpl1->id, // PIC: pic_budi
            'reporter_id' => $this->guru->id,
            'description' => 'Kipas exhaust pendingin ruangan berbunyi sangat bising.',
            'photo_path' => null,
            'status_repair' => 'Belum Diperbaiki',
            'handler_id' => $this->picBudi->id,
        ]);

        // pic_budi memperbarui status menjadi 'Menunggu Spare Part'
        $response1 = $this->actingAs($this->picBudi)->patch("/pengaduan/{$complaint->id}/status", [
            'status_repair' => 'Menunggu Spare Part',
        ]);
        $response1->assertSessionHas('success');
        $complaint->refresh();
        $this->assertEquals('Menunggu Spare Part', $complaint->status_repair);

        // pic_budi memperbarui status menjadi 'Sudah Diperbaiki'
        $response2 = $this->actingAs($this->picBudi)->patch("/pengaduan/{$complaint->id}/status", [
            'status_repair' => 'Sudah Diperbaiki',
        ]);
        $response2->assertSessionHas('success');
        $complaint->refresh();
        $this->assertEquals('Sudah Diperbaiki', $complaint->status_repair);

        // Bersihkan data uji
        $complaint->delete();
    }

    public function test_pic_lain_dilarang_mengubah_status_ruangan_yang_bukan_tanggung_jawabnya(): void
    {
        // Komplain berada di Lab RPL 1 (tanggung jawab pic_budi)
        $complaint = Complaint::create([
            'room_id' => $this->labRpl1->id,
            'reporter_id' => $this->guru->id,
            'description' => 'Kabel stopkontak deret B terkelupas.',
            'photo_path' => null,
            'status_repair' => 'Belum Diperbaiki',
            'handler_id' => $this->picBudi->id,
        ]);

        // pic_siti (Pengelola Gedung/Aula, bukan PIC Lab 1) mencoba mengubah status
        // SISTEM HARUS MENOLAK OTORISASI
        $response = $this->actingAs($this->picSiti)->patch("/pengaduan/{$complaint->id}/status", [
            'status_repair' => 'Sudah Diperbaiki',
        ]);

        $response->assertSessionHas('error');

        $complaint->refresh();
        $this->assertEquals('Belum Diperbaiki', $complaint->status_repair);

        // Bersihkan data uji
        $complaint->delete();
    }

    public function test_guru_dilarang_mengubah_status_perbaikan_pic(): void
    {
        $complaint = Complaint::create([
            'room_id' => $this->labRpl1->id,
            'reporter_id' => $this->guru->id,
            'description' => 'Lampu neon depan papan tulis berkedip-kedip.',
            'photo_path' => null,
            'status_repair' => 'Belum Diperbaiki',
            'handler_id' => $this->picBudi->id,
        ]);

        $response = $this->actingAs($this->guru)->patch("/pengaduan/{$complaint->id}/status", [
            'status_repair' => 'Sudah Diperbaiki',
        ]);

        $response->assertSessionHas('error');
        $complaint->refresh();
        $this->assertEquals('Belum Diperbaiki', $complaint->status_repair);

        // Bersihkan data uji
        $complaint->delete();
    }

    public function test_admin_dilarang_mengubah_status_perbaikan_fasilitas(): void
    {
        $complaint = Complaint::create([
            'room_id' => $this->labRpl1->id,
            'reporter_id' => $this->guru->id,
            'description' => 'Pendingin AC meneteskan air ke lantai lab.',
            'photo_path' => null,
            'status_repair' => 'Belum Diperbaiki',
            'handler_id' => $this->picBudi->id,
        ]);

        // Admin mencoba mengubah status perbaikan (harus ditolak, hanya PIC yang berwenang)
        $response = $this->actingAs($this->admin)->patch("/pengaduan/{$complaint->id}/status", [
            'status_repair' => 'Sudah Diperbaiki',
        ]);

        $response->assertSessionHas('error');
        $complaint->refresh();
        $this->assertEquals('Belum Diperbaiki', $complaint->status_repair);

        // Bersihkan data uji
        $complaint->delete();
    }

    public function test_validasi_menolak_status_di_luar_tiga_kondisi_mutlak(): void
    {
        $complaint = Complaint::create([
            'room_id' => $this->labRpl1->id,
            'reporter_id' => $this->guru->id,
            'description' => 'Kursi putar lab hidrolik patah.',
            'photo_path' => null,
            'status_repair' => 'Belum Diperbaiki',
            'handler_id' => $this->picBudi->id,
        ]);

        // Status acak di luar enum
        $response = $this->actingAs($this->picBudi)->patch("/pengaduan/{$complaint->id}/status", [
            'status_repair' => 'Sedang Diproses Mandiri',
        ]);

        $response->assertSessionHasErrors('status_repair');

        // Bersihkan data uji
        $complaint->delete();
    }

    public function test_admin_pic_dan_guru_dapat_melihat_foto_bukti_pengaduan_kerusakan(): void
    {
        $complaint = Complaint::create([
            'room_id' => $this->labRpl1->id,
            'reporter_id' => $this->guru->id,
            'description' => 'Proyektor ruang lab mati total dan berbau hangus.',
            'photo_path' => 'complaints/contoh_foto_rusak.jpg',
            'status_repair' => 'Belum Diperbaiki',
            'handler_id' => $this->picBudi->id,
        ]);

        // 1. Admin melihat foto bukti kerusakan
        $resAdmin = $this->actingAs($this->admin)->get('/pengaduan');
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('complaints/contoh_foto_rusak.jpg');
        $resAdmin->assertSee('Lihat Bukti Foto');
        $resAdmin->assertSee('photoPreviewModal');

        // 2. PIC ruangan melihat foto bukti kerusakan
        $resPic = $this->actingAs($this->picBudi)->get('/pengaduan');
        $resPic->assertStatus(200);
        $resPic->assertSee('complaints/contoh_foto_rusak.jpg');
        $resPic->assertSee('Lihat Bukti Foto');
        $resPic->assertSee('photoPreviewModal');

        // 3. Guru (pelapor) melihat foto bukti kerusakan miliknya
        $resGuru = $this->actingAs($this->guru)->get('/pengaduan');
        $resGuru->assertStatus(200);
        $resGuru->assertSee('complaints/contoh_foto_rusak.jpg');
        $resGuru->assertSee('Lihat Bukti Foto');
        $resGuru->assertSee('photoPreviewModal');

        // Bersihkan data uji
        $complaint->delete();
    }

    public function test_pengaduan_status_sudah_diperbaiki_otomatis_mencatat_repaired_at(): void
    {
        $complaint = Complaint::create([
            'room_id' => $this->labRpl1->id,
            'reporter_id' => $this->guru->id,
            'description' => 'Saklar lampu utama rusak tidak bisa dinyalakan.',
            'status_repair' => 'Belum Diperbaiki',
            'handler_id' => $this->picBudi->id,
            'reported_at' => now(),
        ]);

        $this->assertNull($complaint->repaired_at);

        // PIC mengubah status menjadi 'Sudah Diperbaiki'
        $this->actingAs($this->picBudi)->patch("/pengaduan/{$complaint->id}/status", [
            'status_repair' => 'Sudah Diperbaiki',
            'repair_notes' => 'Saklar telah diganti dengan komponen baru.',
        ]);

        $complaint->refresh();
        $this->assertEquals('Sudah Diperbaiki', $complaint->status_repair);
        $this->assertNotNull($complaint->repaired_at);

        $complaint->delete();
    }

    public function test_pengaduan_status_sudah_diperbaiki_otomatis_terhapus_setelah_24_jam(): void
    {
        // 1. Buat pengaduan yang sudah diperbaiki 25 jam yang lalu
        $complaintExpired = Complaint::create([
            'room_id' => $this->labRpl1->id,
            'reporter_id' => $this->guru->id,
            'description' => 'Kabel stop kontak kendor di baris depan.',
            'status_repair' => 'Sudah Diperbaiki',
            'repaired_at' => now()->subHours(25),
            'handler_id' => $this->picBudi->id,
            'reported_at' => now()->subDays(2),
        ]);

        // 2. Buat pengaduan yang baru diperbaiki 2 jam yang lalu (belum 24 jam)
        $complaintActive = Complaint::create([
            'room_id' => $this->labRpl1->id,
            'reporter_id' => $this->guru->id,
            'description' => 'Gagang pintu lab patah separuh.',
            'status_repair' => 'Sudah Diperbaiki',
            'repaired_at' => now()->subHours(2),
            'handler_id' => $this->picBudi->id,
            'reported_at' => now()->subHours(10),
        ]);

        // Jalankan pembersihan otomatis (atau akses halaman /pengaduan)
        $purgedCount = Complaint::purgeResolvedOverdue();
        $this->assertGreaterThanOrEqual(1, $purgedCount);

        // Pengaduan yang > 24 jam harus terhapus
        $this->assertDatabaseMissing('complaints', ['id' => $complaintExpired->id]);

        // Pengaduan yang < 24 jam harus tetap ada di database
        $this->assertDatabaseHas('complaints', ['id' => $complaintActive->id]);

        $complaintActive->delete();
    }

    public function test_user_dapat_menghapus_pengaduan_secara_manual(): void
    {
        $complaint = Complaint::create([
            'room_id' => $this->labRpl1->id,
            'reporter_id' => $this->guru->id,
            'description' => 'Mouse USB lab tidak terdeteksi di port 3.',
            'status_repair' => 'Belum Diperbaiki',
            'handler_id' => $this->picBudi->id,
            'reported_at' => now(),
        ]);

        // Pelapor menghapus pengaduannya sendiri secara manual
        $response = $this->actingAs($this->guru)->delete("/pengaduan/{$complaint->id}");
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('complaints', ['id' => $complaint->id]);
    }

    public function test_pic_dapat_menghapus_pengaduan_ruangannya_secara_manual(): void
    {
        $complaint = Complaint::create([
            'room_id' => $this->labRpl1->id,
            'reporter_id' => $this->guru->id,
            'description' => 'Keyboard tombol enter lepas.',
            'status_repair' => 'Sudah Diperbaiki',
            'repaired_at' => now()->subHours(1),
            'handler_id' => $this->picBudi->id,
            'reported_at' => now(),
        ]);

        // PIC ruangan menghapus pengaduan secara manual
        $response = $this->actingAs($this->picBudi)->delete("/pengaduan/{$complaint->id}");
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('complaints', ['id' => $complaint->id]);
    }

    public function test_artisan_command_purge_resolved_berjalan_sukses(): void
    {
        $this->artisan('complaints:purge-resolved')
            ->assertSuccessful();
    }
}
