<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class EventConflictTest extends TestCase
{
    protected User $admin;
    protected User $guru;
    protected Room $room1;
    protected Room $room2;
    protected string $testDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'admin')->first();
        $this->guru = User::where('username', 'guru_andi')->first();
        $this->room1 = Room::where('room_name', 'Lab Komputer RPL 1')->first();
        $this->room2 = Room::where('room_name', 'Lab Komputer RPL 2')->first();

        // Gunakan tanggal 10 hari ke depan agar tidak bertabrakan dengan data seeder
        $this->testDate = Carbon::today()->addDays(10)->toDateString();

        Event::where('date', $this->testDate)->delete();
    }

    protected function tearDown(): void
    {
        Event::where('date', $this->testDate)->delete();
        parent::tearDown();
    }

    public function test_guru_dapat_mengajukan_peminjaman_ruang_pada_jadwal_kosong(): void
    {
        $response = $this->actingAs($this->guru)->post('/agenda', [
            'event_name' => 'Praktikum Basis Data Lanjut',
            'room_id' => $this->room1->id,
            'date' => $this->testDate,
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        $response->assertRedirect('/agenda');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('events', [
            'event_name' => 'Praktikum Basis Data Lanjut',
            'room_id' => $this->room1->id,
            'date' => $this->testDate,
            'status' => 'pending',
            'pic_id' => $this->guru->id,
        ]);
    }

    public function test_admin_mengajukan_agenda_otomatis_berstatus_approved(): void
    {
        $response = $this->actingAs($this->admin)->post('/agenda', [
            'event_name' => 'Sosialisasi PKL Siswa Tingkat XII',
            'room_id' => $this->room1->id,
            'date' => $this->testDate,
            'start_time' => '13:00',
            'end_time' => '15:00',
        ]);

        $response->assertRedirect('/agenda');
        $this->assertDatabaseHas('events', [
            'event_name' => 'Sosialisasi PKL Siswa Tingkat XII',
            'status' => 'approved',
            'approved_by' => $this->admin->id,
        ]);
    }

    public function test_backend_menolak_pengajuan_jika_waktu_bertabrakan_persis(): void
    {
        // Event eksis: 08:00 - 10:00
        Event::create([
            'room_id' => $this->room1->id,
            'pic_id' => $this->admin->id,
            'event_name' => 'Workshop Asesmen Sekolah',
            'date' => $this->testDate,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'approved',
            'approved_by' => $this->admin->id,
        ]);

        // Coba ajukan jam yang persis sama
        $response = $this->actingAs($this->guru)->post('/agenda', [
            'event_name' => 'Bimbingan LKS RPL',
            'room_id' => $this->room1->id,
            'date' => $this->testDate,
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        $response->assertSessionHasErrors('start_time');
    }

    public function test_backend_menolak_pengajuan_jika_waktu_beririsan_sebagian_awal(): void
    {
        // Event eksis: 09:00 - 11:00
        Event::create([
            'room_id' => $this->room1->id,
            'pic_id' => $this->admin->id,
            'event_name' => 'Seminar Karir Alumni',
            'date' => $this->testDate,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'status' => 'approved',
        ]);

        // Coba ajukan 08:00 - 09:30 (irisan 09:00 - 09:30)
        $response = $this->actingAs($this->guru)->post('/agenda', [
            'event_name' => 'Simulasi Tryout',
            'room_id' => $this->room1->id,
            'date' => $this->testDate,
            'start_time' => '08:00',
            'end_time' => '09:30',
        ]);

        $response->assertSessionHasErrors('start_time');
    }

    public function test_backend_menolak_pengajuan_jika_waktu_beririsan_sebagian_akhir(): void
    {
        // Event eksis: 09:00 - 11:00
        Event::create([
            'room_id' => $this->room1->id,
            'pic_id' => $this->admin->id,
            'event_name' => 'Seminar Karir Alumni',
            'date' => $this->testDate,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'status' => 'approved',
        ]);

        // Coba ajukan 10:30 - 12:00 (irisan 10:30 - 11:00)
        $response = $this->actingAs($this->guru)->post('/agenda', [
            'event_name' => 'Simulasi Wawancara',
            'room_id' => $this->room1->id,
            'date' => $this->testDate,
            'start_time' => '10:30',
            'end_time' => '12:00',
        ]);

        $response->assertSessionHasErrors('start_time');
    }

    public function test_backend_menolak_pengajuan_jika_waktu_mencakup_seluruh_agenda_eksis(): void
    {
        // Event eksis: 09:00 - 11:00
        Event::create([
            'room_id' => $this->room1->id,
            'pic_id' => $this->admin->id,
            'event_name' => 'Seminar Karir Alumni',
            'date' => $this->testDate,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'status' => 'approved',
        ]);

        // Coba ajukan 07:00 - 13:00 (mencakup 09:00 - 11:00)
        $response = $this->actingAs($this->guru)->post('/agenda', [
            'event_name' => 'Pekan Budaya Sekolah',
            'room_id' => $this->room1->id,
            'date' => $this->testDate,
            'start_time' => '07:00',
            'end_time' => '13:00',
        ]);

        $response->assertSessionHasErrors('start_time');
    }

    public function test_backend_mengizinkan_jadwal_bersebelahan_back_to_back(): void
    {
        // Event eksis: 08:00 - 10:00
        Event::create([
            'room_id' => $this->room1->id,
            'pic_id' => $this->admin->id,
            'event_name' => 'Workshop Pagi',
            'date' => $this->testDate,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'approved',
        ]);

        // Ajukan tepat setelahnya: 10:00 - 12:00 (HARUS LOLOS / BUKAN TABRAKAN)
        $response = $this->actingAs($this->guru)->post('/agenda', [
            'event_name' => 'Workshop Siang',
            'room_id' => $this->room1->id,
            'date' => $this->testDate,
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $response->assertRedirect('/agenda');
        $response->assertSessionHas('success');
    }

    public function test_backend_mengizinkan_waktu_sama_pada_ruangan_berbeda(): void
    {
        // Event eksis di Room 1: 08:00 - 10:00
        Event::create([
            'room_id' => $this->room1->id,
            'pic_id' => $this->admin->id,
            'event_name' => 'Kegiatan Lab 1',
            'date' => $this->testDate,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'approved',
        ]);

        // Ajukan di Room 2 jam 08:00 - 10:00 (HARUS LOLOS)
        $response = $this->actingAs($this->guru)->post('/agenda', [
            'event_name' => 'Kegiatan Lab 2',
            'room_id' => $this->room2->id,
            'date' => $this->testDate,
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        $response->assertRedirect('/agenda');
        $response->assertSessionHas('success');
    }

    public function test_backend_mengizinkan_pengajuan_jika_agenda_lama_ditolak(): void
    {
        // Event eksis berstatus rejected: 08:00 - 10:00
        Event::create([
            'room_id' => $this->room1->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Agenda Ditolak Sebelumnya',
            'date' => $this->testDate,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'rejected',
        ]);

        // Ajukan ulang pada jam yang sama (HARUS LOLOS KARENA STATUS REJECTED TIDAK MENGUNCI RUANG)
        $response = $this->actingAs($this->guru)->post('/agenda', [
            'event_name' => 'Agenda Pengganti Baru',
            'room_id' => $this->room1->id,
            'date' => $this->testDate,
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        $response->assertRedirect('/agenda');
        $response->assertSessionHas('success');
    }

    public function test_admin_dapat_menyetujui_dan_menolak_agenda(): void
    {
        $event = Event::create([
            'room_id' => $this->room1->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Pengujian Persetujuan Admin',
            'date' => $this->testDate,
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'status' => 'pending',
        ]);

        // Approve
        $resApprove = $this->actingAs($this->admin)->post("/agenda/{$event->id}/setujui");
        $resApprove->assertSessionHas('success');
        $event->refresh();
        $this->assertEquals('approved', $event->status);

        // Reject (dengan alasan wajib penolakan)
        $resReject = $this->actingAs($this->admin)->post("/agenda/{$event->id}/tolak", [
            'alasan_penolakan' => 'Jadwal bentrok dengan kegiatan dinas sekolah.',
        ]);
        $resReject->assertSessionHas('info');
        $event->refresh();
        $this->assertEquals('rejected', $event->status);
        $this->assertEquals('Jadwal bentrok dengan kegiatan dinas sekolah.', $event->alasan_penolakan);
    }

    public function test_api_cek_ketersediaan_ruang_real_time(): void
    {
        $response = $this->actingAs($this->guru)->getJson("/api/cek-ketersediaan-ruang?room_id={$this->room1->id}&date={$this->testDate}&start_time=08:00&end_time=10:00");
        $response->assertStatus(200);
        $response->assertJson([
            'available' => true,
        ]);
    }
}
