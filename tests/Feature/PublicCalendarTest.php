<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class PublicCalendarTest extends TestCase
{
    protected function tearDown(): void
    {
        // Bersihkan data dummy setelah pengujian
        Event::where('event_name', 'like', 'TEST_PUB_%')->delete();
        Room::where('room_name', 'like', 'TEST_ROOM_PUB_%')->delete();

        parent::tearDown();
    }

    public function test_publik_dapat_mengakses_kalender_tanpa_login(): void
    {
        $response = $this->get('/kalender-sekolah');

        $response->assertStatus(200);
        $response->assertSeeText('Kalender Agenda & Penggunaan Ruang');
        $response->assertSeeText('Transparansi Fasilitas & Agenda Sekolah');
    }

    public function test_kalender_hanya_menampilkan_agenda_berstatus_approved(): void
    {
        $pic = User::where('username', 'guru_andi')->first();
        $admin = User::where('username', 'admin')->first();
        $room = Room::first();

        $today = Carbon::today()->toDateString();

        // 1. Agenda Approved
        $approvedEvent = Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => 'TEST_PUB_AGENDA_APPROVED_RESMI',
            'date' => $today,
            'start_time' => '07:30:00',
            'end_time' => '09:00:00',
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        // 2. Agenda Pending
        $pendingEvent = Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => 'TEST_PUB_AGENDA_PENDING_RAHASIA',
            'date' => $today,
            'start_time' => '09:30:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
        ]);

        // 3. Agenda Rejected
        $rejectedEvent = Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => 'TEST_PUB_AGENDA_REJECTED_BATAL',
            'date' => $today,
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'rejected',
        ]);

        $response = $this->get('/kalender-sekolah');

        $response->assertStatus(200);
        $response->assertSee('TEST_PUB_AGENDA_APPROVED_RESMI');
        $response->assertDontSee('TEST_PUB_AGENDA_PENDING_RAHASIA');
        $response->assertDontSee('TEST_PUB_AGENDA_REJECTED_BATAL');
    }

    public function test_navigasi_bulan_dan_tahun_kalender(): void
    {
        $pic = User::where('username', 'guru_andi')->first();
        $admin = User::where('username', 'admin')->first();
        $room = Room::first();

        // Buat agenda di bulan depan
        $nextMonth = Carbon::today()->addMonth();
        $eventNextMonth = Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => 'TEST_PUB_EVENT_BULAN_DEPAN',
            'date' => $nextMonth->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        // Akses bulan sekarang -> tidak melihat agenda bulan depan
        $resCurrent = $this->get('/kalender-sekolah?month=' . Carbon::today()->month . '&year=' . Carbon::today()->year);
        $resCurrent->assertStatus(200);
        $resCurrent->assertDontSee('TEST_PUB_EVENT_BULAN_DEPAN');

        // Akses bulan depan via parameter query -> agenda muncul
        $resNext = $this->get('/kalender-sekolah?month=' . $nextMonth->month . '&year=' . $nextMonth->year);
        $resNext->assertStatus(200);
        $resNext->assertSee('TEST_PUB_EVENT_BULAN_DEPAN');
    }

    public function test_filter_agenda_berdasarkan_ruangan(): void
    {
        $pic = User::where('username', 'guru_andi')->first();
        $admin = User::where('username', 'admin')->first();

        $roomA = Room::create([
            'room_name' => 'TEST_ROOM_PUB_A',
            'location' => 'Gedung A',
            'capacity' => 40,
            'pic_id' => $pic->id,
            'is_active' => true,
        ]);

        $roomB = Room::create([
            'room_name' => 'TEST_ROOM_PUB_B',
            'location' => 'Gedung B',
            'capacity' => 50,
            'pic_id' => $pic->id,
            'is_active' => true,
        ]);

        $today = Carbon::today()->toDateString();

        Event::create([
            'room_id' => $roomA->id,
            'pic_id' => $pic->id,
            'event_name' => 'TEST_PUB_EVENT_RUANG_A',
            'date' => $today,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        Event::create([
            'room_id' => $roomB->id,
            'pic_id' => $pic->id,
            'event_name' => 'TEST_PUB_EVENT_RUANG_B',
            'date' => $today,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        // Filter hanya room A
        $responseA = $this->get('/kalender-sekolah?room_id=' . $roomA->id);
        $responseA->assertStatus(200);
        $responseA->assertSee('TEST_PUB_EVENT_RUANG_A');
        $responseA->assertDontSee('TEST_PUB_EVENT_RUANG_B');

        // Filter hanya room B
        $responseB = $this->get('/kalender-sekolah?room_id=' . $roomB->id);
        $responseB->assertStatus(200);
        $responseB->assertSee('TEST_PUB_EVENT_RUANG_B');
        $responseB->assertDontSee('TEST_PUB_EVENT_RUANG_A');
    }

    public function test_pencarian_agenda_berdasarkan_kata_kunci(): void
    {
        $pic = User::where('username', 'guru_andi')->first();
        $admin = User::where('username', 'admin')->first();
        $room = Room::first();

        $today = Carbon::today()->toDateString();

        Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => 'TEST_PUB_SEMINAR_ROBOTIK_KODING',
            'date' => $today,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => 'TEST_PUB_LOMBA_BOLA_VOLI',
            'date' => $today,
            'start_time' => '10:30:00',
            'end_time' => '12:00:00',
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        // Cari kata kunci 'ROBOTIK'
        $response = $this->get('/kalender-sekolah?search=ROBOTIK');
        $response->assertStatus(200);
        $response->assertSee('TEST_PUB_SEMINAR_ROBOTIK_KODING');
        $response->assertDontSee('TEST_PUB_LOMBA_BOLA_VOLI');
    }

    public function test_mode_tampilan_daftar_agenda(): void
    {
        $pic = User::where('username', 'guru_andi')->first();
        $admin = User::where('username', 'admin')->first();
        $room = Room::first();

        $today = Carbon::today()->toDateString();

        Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => 'TEST_PUB_AGENDA_MODE_DAFTAR',
            'date' => $today,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        $response = $this->get('/kalender-sekolah?view=list');
        $response->assertStatus(200);
        $response->assertSee('Daftar Agenda Terjadwal');
        $response->assertSee('TEST_PUB_AGENDA_MODE_DAFTAR');
        $response->assertSee('Lihat Detail Acara');
    }

    public function test_endpoint_kalender_mendukung_format_json_untuk_integrasi_api_dan_mobile(): void
    {
        $pic = User::where('username', 'guru_andi')->first();
        $admin = User::where('username', 'admin')->first();
        $room = Room::first();

        $today = Carbon::today()->toDateString();

        Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => 'TEST_PUB_JSON_INTEGRATION',
            'date' => $today,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        $response = $this->getJson('/kalender-sekolah');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonFragment([
            'event_name' => 'TEST_PUB_JSON_INTEGRATION',
        ]);
    }
}
