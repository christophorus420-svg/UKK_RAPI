<?php

namespace Tests\Feature;

use App\Models\BookingSession;
use App\Models\Event;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class EventEditAndMultiSessionTest extends TestCase
{
    protected User $admin;
    protected User $guru;
    protected User $otherUser;
    protected Room $room1;
    protected Room $room2;
    protected BookingSession $sesi1;
    protected BookingSession $sesi2;
    protected BookingSession $sesi3;
    protected string $testDate;
    protected string $pastDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'admin')->firstOrFail();
        $this->guru = User::where('username', 'guru_andi')->firstOrFail();
        $this->otherUser = User::where('username', 'pic_budi')->firstOrFail();
        $this->room1 = Room::where('room_name', 'Lab Komputer RPL 1')->firstOrFail();
        $this->room2 = Room::where('room_name', 'Lab Komputer RPL 2')->firstOrFail();

        $sessions = BookingSession::where('is_active', true)->orderBy('start_time')->get();
        $this->sesi1 = $sessions->get(0);
        $this->sesi2 = $sessions->get(1);
        $this->sesi3 = $sessions->get(2);

        $this->testDate = Carbon::today()->addDays(15)->toDateString();
        $this->pastDate = Carbon::today()->subDays(5)->toDateString();

        Event::whereIn('date', [$this->testDate, $this->pastDate])->delete();
    }

    protected function tearDown(): void
    {
        Event::whereIn('date', [$this->testDate, $this->pastDate])->delete();
        parent::tearDown();
    }

    public function test_halaman_ajukan_menampilkan_checkbox_multi_sesi(): void
    {
        $response = $this->actingAs($this->guru)->get('/agenda/ajukan');

        $response->assertStatus(200);
        $response->assertSee('name="session_ids[]"', false);
        $response->assertSee('type="checkbox"', false);
        $response->assertSee('Bisa Lebih dari Satu');
    }

    public function test_guru_dapat_memilih_lebih_dari_satu_sesi_berurutan(): void
    {
        $response = $this->actingAs($this->guru)->post('/agenda', [
            'event_name' => 'Praktikum Pemrograman Web 3 Jam',
            'room_id' => $this->room1->id,
            'date' => $this->testDate,
            'session_ids' => [$this->sesi1->id, $this->sesi2->id, $this->sesi3->id],
        ]);

        $response->assertRedirect('/agenda');
        $response->assertSessionHas('success');

        $event = Event::where('event_name', 'Praktikum Pemrograman Web 3 Jam')->first();
        $this->assertNotNull($event);
        $this->assertEquals(substr($this->sesi1->start_time, 0, 5), substr($event->start_time, 0, 5));
        $this->assertEquals(substr($this->sesi3->end_time, 0, 5), substr($event->end_time, 0, 5));
        $this->assertEquals('pending', $event->status);
        $this->assertStringContainsString('s.d.', (string) $event->session_label);

        // Sesi 2 di antara Sesi 1 dan Sesi 3 harus otomatis terlindungi dari bentrok
        $conflictResponse = $this->actingAs($this->otherUser)->post('/agenda', [
            'event_name' => 'Bentrok Tengah',
            'room_id' => $this->room1->id,
            'date' => $this->testDate,
            'session_ids' => [$this->sesi2->id],
        ]);

        $conflictResponse->assertSessionHasErrors(['session_ids']);
    }

    public function test_guru_dapat_memilih_lebih_dari_satu_sesi_terpisah_tanpa_mengunci_sesi_tengah(): void
    {
        $response = $this->actingAs($this->guru)->post('/agenda', [
            'event_name' => 'Kuis Sesi Terpisah',
            'room_id' => $this->room1->id,
            'date' => $this->testDate,
            'session_ids' => [$this->sesi1->id, $this->sesi3->id],
        ]);

        $response->assertRedirect('/agenda');
        $response->assertSessionHas('success');

        $created = Event::where('event_name', 'Kuis Sesi Terpisah')->orderBy('start_time')->get();
        $this->assertCount(2, $created);
        $this->assertEquals(substr($this->sesi1->start_time, 0, 5), substr($created[0]->start_time, 0, 5));
        $this->assertEquals(substr($this->sesi3->start_time, 0, 5), substr($created[1]->start_time, 0, 5));

        // Sesi 2 (di tengah) tetap kosong dan dapat dipinjam guru lain
        $middleBooking = $this->actingAs($this->otherUser)->post('/agenda', [
            'event_name' => 'Slot Sesi 2 Tetap Bebas',
            'room_id' => $this->room1->id,
            'date' => $this->testDate,
            'session_ids' => [$this->sesi2->id],
        ]);

        $middleBooking->assertRedirect('/agenda');
        $this->assertDatabaseHas('events', ['event_name' => 'Slot Sesi 2 Tetap Bebas']);
    }

    public function test_cek_ketersediaan_ajax_mendukung_multi_sesi_dan_exclude_event_id(): void
    {
        $ownEvent = Event::create([
            'room_id' => $this->room1->id,
            'session_id' => $this->sesi1->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Event Milik Sendiri',
            'date' => $this->testDate,
            'start_time' => substr($this->sesi1->start_time, 0, 5),
            'end_time' => substr($this->sesi1->end_time, 0, 5),
            'status' => 'pending',
        ]);

        Event::create([
            'room_id' => $this->room1->id,
            'session_id' => $this->sesi3->id,
            'pic_id' => $this->otherUser->id,
            'event_name' => 'Event Orang Lain Sesi 3',
            'date' => $this->testDate,
            'start_time' => substr($this->sesi3->start_time, 0, 5),
            'end_time' => substr($this->sesi3->end_time, 0, 5),
            'status' => 'approved',
            'approved_by' => $this->admin->id,
        ]);

        // Saat edit ownEvent ke Sesi 1 + Sesi 2: tersedia karena ownEvent dikecualikan
        $okUrl = '/api/cek-ketersediaan-ruang?' . http_build_query([
            'room_id' => $this->room1->id,
            'date' => $this->testDate,
            'exclude_event_id' => $ownEvent->id,
            'session_ids' => [$this->sesi1->id, $this->sesi2->id],
        ]);

        $this->actingAs($this->guru)->getJson($okUrl)
            ->assertStatus(200)
            ->assertJson(['available' => true]);

        // Jika mencoba meluas ke Sesi 3 milik orang lain -> ditolak
        $conflictUrl = '/api/cek-ketersediaan-ruang?' . http_build_query([
            'room_id' => $this->room1->id,
            'date' => $this->testDate,
            'exclude_event_id' => $ownEvent->id,
            'session_ids' => [$this->sesi1->id, $this->sesi2->id, $this->sesi3->id],
        ]);

        $this->actingAs($this->guru)->getJson($conflictUrl)
            ->assertStatus(200)
            ->assertJson(['available' => false]);
    }

    public function test_pemilik_dapat_mengedit_peminjaman_ruang_dan_agenda_sendiri(): void
    {
        $event = Event::create([
            'room_id' => $this->room1->id,
            'session_id' => $this->sesi1->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Agenda Awal',
            'date' => $this->testDate,
            'start_time' => substr($this->sesi1->start_time, 0, 5),
            'end_time' => substr($this->sesi1->end_time, 0, 5),
            'status' => 'pending',
        ]);

        $this->actingAs($this->guru)
            ->get("/agenda/{$event->id}/edit")
            ->assertStatus(200)
            ->assertSee('Agenda Awal');

        $updateResponse = $this->actingAs($this->guru)->put("/agenda/{$event->id}", [
            'event_name' => 'Agenda Setelah Revisi Multi Sesi',
            'room_id' => $this->room2->id,
            'date' => $this->testDate,
            'session_ids' => [$this->sesi1->id, $this->sesi2->id],
        ]);

        $updateResponse->assertRedirect('/agenda');
        $updateResponse->assertSessionHas('success');

        $event->refresh();
        $this->assertEquals('Agenda Setelah Revisi Multi Sesi', $event->event_name);
        $this->assertEquals($this->room2->id, $event->room_id);
        $this->assertEquals(substr($this->sesi1->start_time, 0, 5), substr($event->start_time, 0, 5));
        $this->assertEquals(substr($this->sesi2->end_time, 0, 5), substr($event->end_time, 0, 5));
        $this->assertEquals('pending', $event->status);
    }

    public function test_guru_mengedit_agenda_approved_mendatang_mereset_status_ke_pending(): void
    {
        $event = Event::create([
            'room_id' => $this->room1->id,
            'session_id' => $this->sesi1->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Sudah Approved Awalnya',
            'date' => $this->testDate,
            'start_time' => substr($this->sesi1->start_time, 0, 5),
            'end_time' => substr($this->sesi1->end_time, 0, 5),
            'status' => 'approved',
            'approved_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->guru)->put("/agenda/{$event->id}", [
            'event_name' => 'Diubah Oleh Guru',
            'room_id' => $this->room2->id,
            'date' => $this->testDate,
            'session_ids' => [$this->sesi1->id],
        ]);

        $response->assertRedirect('/agenda');
        $event->refresh();
        $this->assertEquals('Diubah Oleh Guru', $event->event_name);
        $this->assertEquals('pending', $event->status);
        $this->assertNull($event->approved_by);
    }

    public function test_admin_mengedit_agenda_approved_mempertahankan_status_approved(): void
    {
        $event = Event::create([
            'room_id' => $this->room1->id,
            'session_id' => $this->sesi1->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Agenda Approved Admin Edit',
            'date' => $this->testDate,
            'start_time' => substr($this->sesi1->start_time, 0, 5),
            'end_time' => substr($this->sesi1->end_time, 0, 5),
            'status' => 'approved',
            'approved_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put("/agenda/{$event->id}", [
            'event_name' => 'Agenda Approved Diubah Admin',
            'room_id' => $this->room1->id,
            'date' => $this->testDate,
            'session_ids' => [$this->sesi1->id, $this->sesi2->id],
        ]);

        $response->assertRedirect('/agenda');
        $event->refresh();
        $this->assertEquals('Agenda Approved Diubah Admin', $event->event_name);
        $this->assertEquals('approved', $event->status);
        $this->assertEquals($this->admin->id, $event->approved_by);
    }

    public function test_pengguna_lain_dilarang_mengedit_agenda_orang_lain_dan_validasi_bentrok_saat_edit(): void
    {
        $guruEvent = Event::create([
            'room_id' => $this->room1->id,
            'session_id' => $this->sesi1->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Agenda Rahasia Guru',
            'date' => $this->testDate,
            'start_time' => substr($this->sesi1->start_time, 0, 5),
            'end_time' => substr($this->sesi1->end_time, 0, 5),
            'status' => 'pending',
        ]);

        // Orang lain dilarang akses edit
        $this->actingAs($this->otherUser)
            ->get("/agenda/{$guruEvent->id}/edit")
            ->assertRedirect('/agenda')
            ->assertSessionHas('error');

        $this->actingAs($this->otherUser)
            ->put("/agenda/{$guruEvent->id}", [
                'event_name' => 'Dibajak',
                'room_id' => $this->room1->id,
                'date' => $this->testDate,
                'session_ids' => [$this->sesi1->id],
            ])
            ->assertRedirect('/agenda')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('events', ['id' => $guruEvent->id, 'event_name' => 'Agenda Rahasia Guru']);

        // Guru juga dilarang mengedit agenda approved yang sudah lewat di masa lampau
        $pastEvent = Event::create([
            'room_id' => $this->room1->id,
            'session_id' => $this->sesi1->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Agenda Masa Lampau',
            'date' => $this->pastDate,
            'start_time' => substr($this->sesi1->start_time, 0, 5),
            'end_time' => substr($this->sesi1->end_time, 0, 5),
            'status' => 'approved',
            'approved_by' => $this->admin->id,
        ]);

        $this->actingAs($this->guru)
            ->get("/agenda/{$pastEvent->id}/edit")
            ->assertRedirect('/agenda')
            ->assertSessionHas('error');

        // Edit dengan sesi berjeda pada satu event ditolak dengan pesan jelas
        $this->actingAs($this->guru)
            ->put("/agenda/{$guruEvent->id}", [
                'event_name' => 'Coba Sesi Terpisah Saat Edit',
                'room_id' => $this->room1->id,
                'date' => $this->testDate,
                'session_ids' => [$this->sesi1->id, $this->sesi3->id],
            ])
            ->assertSessionHasErrors(['session_ids']);
    }

    public function test_tombol_aksi_agenda_ditumpuk_vertikal_dari_lapor_masalah_ke_peringatan_wa(): void
    {
        $approvedEvent = Event::create([
            'room_id' => $this->room1->id,
            'session_id' => $this->sesi1->id,
            'pic_id' => $this->guru->id,
            'event_name' => 'Uji Urutan Tombol Bertumpuk',
            'date' => $this->testDate,
            'start_time' => substr($this->sesi1->start_time, 0, 5),
            'end_time' => substr($this->sesi1->end_time, 0, 5),
            'status' => 'approved',
            'approved_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/agenda');
        $response->assertStatus(200);
        $response->assertSee('flex-col');
        $response->assertSeeInOrder([
            'Uji Urutan Tombol Bertumpuk',
            'Lapor Masalah',
            'Pindah Ruang',
            route('events.edit', $approvedEvent),
            'Peringatkan WA',
        ]);
    }
}

