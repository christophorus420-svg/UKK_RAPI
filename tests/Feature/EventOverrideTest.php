<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class EventOverrideTest extends TestCase
{
    protected User $admin;
    protected User $guru;
    protected Room $room1;
    protected Room $room2;
    protected Room $room3;
    protected string $testDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'admin')->first();
        $this->guru = User::where('username', 'guru_andi')->first();
        $this->room1 = Room::where('room_name', 'Lab Komputer RPL 1')->first();
        $this->room2 = Room::where('room_name', 'Lab Komputer RPL 2')->first();
        $this->room3 = Room::where('room_name', 'Aula Graha Wiyata')->first();

        $this->testDate = Carbon::today()->addDays(12)->toDateString();
        Event::where('date', $this->testDate)->delete();
    }

    protected function tearDown(): void
    {
        Event::where('date', $this->testDate)->delete();
        parent::tearDown();
    }

    public function test_admin_dapat_melihat_form_pindah_ruang(): void
    {
        $event = Event::create([
            'room_id' => $this->room1->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Workshop UI/UX Design',
            'date' => $this->testDate,
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->admin)->get("/agenda/{$event->id}/pindah-ruang");
        $response->assertStatus(200);
        $response->assertSee('Formulir Pemindahan Ruangan Terpinjam');
        $response->assertSee('Workshop UI/UX Design');
    }

    public function test_guru_dilarang_mengakses_form_pindah_ruang(): void
    {
        $event = Event::create([
            'room_id' => $this->room1->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Workshop UI/UX Design',
            'date' => $this->testDate,
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->guru)->get("/agenda/{$event->id}/pindah-ruang");
        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('error');
    }

    public function test_waka_dapat_memindahkan_ruang_dan_mencatat_alasan_otomatis(): void
    {
        $event = Event::create([
            'room_id' => $this->room1->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Latihan Pra-UKK',
            'date' => $this->testDate,
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'status' => 'approved',
        ]);

        $alasan = 'Lab 1 dialihkan darurat untuk Asesmen Provinsi mendadak.';

        $response = $this->actingAs($this->admin)->post("/agenda/{$event->id}/pindah-ruang", [
            'new_room_id' => $this->room2->id,
            'move_reason' => $alasan,
        ]);

        $response->assertRedirect('/agenda');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'room_id' => $this->room2->id,
            'move_reason' => $alasan,
            'approved_by' => $this->admin->id,
        ]);
    }

    public function test_pemindahan_ruang_gagal_jika_alasan_kosong(): void
    {
        $event = Event::create([
            'room_id' => $this->room1->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Ujian Harian RPL',
            'date' => $this->testDate,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->admin)->post("/agenda/{$event->id}/pindah-ruang", [
            'new_room_id' => $this->room2->id,
            'move_reason' => '',
        ]);

        $response->assertSessionHasErrors('move_reason');
    }

    public function test_pemindahan_ruang_gagal_jika_ruang_tujuan_sudah_dipakai(): void
    {
        // Event target pada Room 2: 08:00 - 11:00
        Event::create([
            'room_id' => $this->room2->id,
            'pic_id' => $this->admin->id,
            'event_name' => 'Pelatihan Guru Produktif',
            'date' => $this->testDate,
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'status' => 'approved',
        ]);

        // Event yang hendak dipindahkan (jam sama 08:00 - 11:00 di Room 1)
        $eventToMove = Event::create([
            'room_id' => $this->room1->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Latihan OSN Siswa',
            'date' => $this->testDate,
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'status' => 'approved',
        ]);

        // Coba pindahkan ke Room 2 yang sedang terpakai -> HARUS DITOLAK
        $response = $this->actingAs($this->admin)->post("/agenda/{$eventToMove->id}/pindah-ruang", [
            'new_room_id' => $this->room2->id,
            'move_reason' => 'Lab 1 korsleting listrik mendadak.',
        ]);

        $response->assertSessionHasErrors('new_room_id');
    }

    public function test_guru_menerima_notifikasi_pop_up_dan_alasan_pemindahan_di_dashboard(): void
    {
        $alasan = 'Gedung utama dialihkan untuk Rapat Pleno Komite Sekolah.';

        Event::create([
            'room_id' => $this->room2->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Bimbingan Ekstrakurikuler Web',
            'date' => $this->testDate,
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'approved',
            'approved_by' => $this->admin->id,
            'move_reason' => $alasan,
        ]);

        $response = $this->actingAs($this->guru)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Pemberitahuan Pemindahan Ruangan Kegiatan');
        $response->assertSee('Bimbingan Ekstrakurikuler Web');
        $response->assertSee($this->room2->room_name);
        $response->assertSee($alasan);
    }

    public function test_pemindahan_ruangan_mengirim_notifikasi_whatsapp_ke_peminjam(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response(['status' => true, 'message' => 'Terkirim'], 200),
        ]);

        config(['services.whatsapp.token' => 'dummy_token_test']);

        $this->guru->update(['phone_number' => '081299887766']);

        $event = Event::create([
            'room_id' => $this->room1->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Simulasi Gladi Bersih',
            'date' => $this->testDate,
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->admin)->post("/agenda/{$event->id}/pindah-ruang", [
            'new_room_id' => $this->room2->id,
            'move_reason' => 'Perbaikan instalasi listrik mendadak.',
        ]);

        $response->assertRedirect('/agenda');
        $response->assertSessionHas('success');

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            return str_contains($request['message'], 'Simulasi Gladi Bersih')
                && str_contains($request['message'], 'Lab Komputer RPL 1')
                && str_contains($request['message'], 'Lab Komputer RPL 2');
        });
    }

    public function test_pemindahan_ruangan_tetap_berhasil_meskipun_wa_gagal(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response(['status' => false, 'message' => 'Gateway timeout'], 500),
        ]);

        config(['services.whatsapp.token' => 'dummy_token_test']);

        $event = Event::create([
            'room_id' => $this->room1->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Rapat Pembina Ekstrakurikuler',
            'date' => $this->testDate,
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->admin)->post("/agenda/{$event->id}/pindah-ruang", [
            'new_room_id' => $this->room2->id,
            'move_reason' => 'Dipindahkan ke ruangan berkapasitas lebih besar.',
        ]);

        // Perpindahan tetap berhasil di database
        $response->assertRedirect('/agenda');
        $response->assertSessionHas('success');
        $response->assertSessionHas('whatsapp_move_url');
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'room_id' => $this->room2->id,
        ]);
    }

    public function test_dashboard_admin_menampilkan_button_peringatan_perpindahan_ruangan_via_wa(): void
    {
        $this->guru->update(['phone_number' => '081299887766']);

        $movedEvent = Event::create([
            'room_id' => $this->room2->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Rapat Evaluasi Kurikulum',
            'date' => $this->testDate,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'status' => 'approved',
            'approved_by' => $this->admin->id,
            'move_reason' => 'Lab 1 digunakan ujian praktik kejuruan.',
        ]);

        // Verifikasi URL WA pada model Event
        $waUrl = $movedEvent->whatsapp_move_warning_url;
        $this->assertStringStartsWith('https://wa.me/6281299887766?text=', $waUrl);
        $parsedUrl = parse_url($waUrl);
        parse_str($parsedUrl['query'], $query);
        $this->assertStringContainsString('Rapat Evaluasi Kurikulum', $query['text']);
        $this->assertStringContainsString('Lab 1 digunakan ujian praktik kejuruan.', $query['text']);
        $this->assertStringContainsString($this->room2->room_name, $query['text']);
        $this->assertStringContainsString($this->guru->fullname, $query['text']);

        // Verifikasi tampil di Dashboard Admin
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Peringatan Perpindahan Ruangan ke Guru');
        $response->assertSee('Peringatkan Guru via WhatsApp');
        $response->assertSee('Rapat Evaluasi Kurikulum');
        $response->assertSee('https://wa.me/6281299887766', false);

        // Verifikasi guru tidak melihat card admin tersebut
        $responseGuru = $this->actingAs($this->guru)->get('/dashboard');
        $responseGuru->assertStatus(200);
        $responseGuru->assertDontSee('Peringatan Perpindahan Ruangan ke Guru');
    }

    public function test_url_peringatan_wa_pra_pemindahan_dan_halaman_agenda_untuk_admin(): void
    {
        $this->guru->update(['phone_number' => '081299887766']);

        $event = Event::create([
            'room_id' => $this->room1->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Seminar Persiapan PKL',
            'date' => $this->testDate,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'approved',
        ]);

        // Pre-move warning message
        $parsedUrl = parse_url($event->whatsapp_move_warning_url);
        parse_str($parsedUrl['query'], $query);
        $this->assertStringContainsString('Seminar Persiapan PKL', $query['text']);
        $this->assertStringContainsString($this->room1->room_name, $query['text']);

        // Tampil di halaman agenda untuk admin, tidak untuk guru
        $responseAdmin = $this->actingAs($this->admin)->get('/agenda');
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Peringatkan WA');
        $responseAdmin->assertSee('https://wa.me/6281299887766', false);

        $responseGuru = $this->actingAs($this->guru)->get('/agenda');
        $responseGuru->assertStatus(200);
        $responseGuru->assertDontSee('Peringatkan WA');
    }
}
