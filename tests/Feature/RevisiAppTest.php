<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RevisiAppTest extends TestCase
{
    protected User $admin;
    protected User $pic;
    protected User $guru;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'admin')->first();
        $this->pic = User::where('username', 'pic_budi')->first();
        $this->guru = User::where('username', 'guru_andi')->first();
        $this->room = Room::where('room_name', 'Lab Komputer RPL 1')->first();
    }

    public function test_peminjaman_multi_hari_berhasil_disimpan(): void
    {
        $startDate = Carbon::today()->addDays(15)->toDateString();
        $endDate = Carbon::today()->addDays(17)->toDateString();

        Event::where('room_id', $this->room->id)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('date', '<=', $endDate)
                  ->whereRaw('COALESCE(end_date, date) >= ?', [$startDate]);
            })->delete();

        $response = $this->actingAs($this->guru)->post('/agenda', [
            'event_name' => 'Lomba Kompetensi Siswa (LKS) 3 Hari',
            'room_id' => $this->room->id,
            'date' => $startDate,
            'end_date' => $endDate,
            'start_time' => '08:00',
            'end_time' => '15:00',
        ]);

        $response->assertRedirect('/agenda');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('events', [
            'event_name' => 'Lomba Kompetensi Siswa (LKS) 3 Hari',
            'date' => $startDate,
            'end_date' => $endDate,
            'status' => 'pending',
        ]);

        // Cleanup
        Event::where('event_name', 'Lomba Kompetensi Siswa (LKS) 3 Hari')->delete();
    }

    public function test_validasi_menolak_peminjaman_yang_bentrok_dengan_event_multi_hari(): void
    {
        $startDate = Carbon::today()->addDays(20)->toDateString();
        $endDate = Carbon::today()->addDays(22)->toDateString();

        // Buat event multi-hari yang disetujui (20 s/d 22, 08:00 - 12:00)
        $multiDayEvent = Event::create([
            'room_id' => $this->room->id,
            'pic_id' => $this->admin->id,
            'event_name' => 'Bootcamp Coding Multi-Hari',
            'date' => $startDate,
            'end_date' => $endDate,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status' => 'approved',
            'approved_by' => $this->admin->id,
        ]);

        // Coba ajukan di tanggal 21 (hari kedua event di atas) pada jam 09:00 - 11:00
        $bentrokDate = Carbon::today()->addDays(21)->toDateString();
        $response = $this->actingAs($this->guru)->post('/agenda', [
            'event_name' => 'Bimbingan Singkat Bentrok',
            'room_id' => $this->room->id,
            'date' => $bentrokDate,
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $response->assertSessionHasErrors('start_time');

        // Cleanup
        $multiDayEvent->delete();
    }

    public function test_daftar_pengaduan_menggunakan_pagination_lima_per_halaman(): void
    {
        $response = $this->actingAs($this->admin)->get('/pengaduan');

        $response->assertStatus(200);
        $complaints = $response->viewData('complaints');
        $this->assertEquals(5, $complaints->perPage());
    }

    public function test_pic_dapat_mencatat_keterangan_dan_waktu_perbaikan_kerusakan(): void
    {
        $complaint = Complaint::create([
            'room_id' => $this->room->id,
            'reporter_id' => $this->guru->id,
            'description' => 'Monitor PC 05 mati total tidak menyala',
            'status_repair' => 'Belum Diperbaiki',
            'handler_id' => $this->pic->id,
            'reported_at' => Carbon::now()->subDays(1),
        ]);

        $nowStr = Carbon::now()->format('Y-m-d H:i:s');
        $response = $this->actingAs($this->pic)->patch("/pengaduan/{$complaint->id}/status", [
            'status_repair' => 'Sudah Diperbaiki',
            'repair_notes' => 'Kabel adaptor VGA diganti unit baru dan diuji normal',
            'repaired_at' => $nowStr,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status_repair' => 'Sudah Diperbaiki',
            'repair_notes' => 'Kabel adaptor VGA diganti unit baru dan diuji normal',
        ]);

        // Cleanup
        $complaint->delete();
    }

    public function test_admin_dapat_mengunduh_template_excel_dan_import_pengguna_secara_massal(): void
    {
        // 1. Unduh template
        $responseDownload = $this->actingAs($this->admin)->get('/pengguna/template-excel');
        $responseDownload->assertStatus(200);
        $responseDownload->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // 2. Import CSV data massal dengan validasi
        $csvContent = "\xEF\xBB\xBFNama Lengkap,Username,Email,Nomor Telepon,Peran,Password\n"
            . "Guru Matematika Baru,guru_matematika,guru_mtk@sekolah.sch.id,081299990001,guru,password123\n"
            . "Teknisi Lab Jaringan,pic_jaringan,pic_jaringan@sekolah.sch.id,081299990002,pic,password123\n"
            . "User Gagal Tanpa Username,,gagal@sekolah.sch.id,081299990003,guru,password123\n"
            . "Duplikat Username,guru_matematika,guru_duplikat@sekolah.sch.id,081299990004,guru,password123\n";

        $file = UploadedFile::fake()->createWithContent('import_pengguna.csv', $csvContent);

        // Hapus user jika sudah ada sebelumnya
        User::whereIn('username', ['guru_matematika', 'pic_jaringan'])->delete();

        $responseImport = $this->actingAs($this->admin)->post('/pengguna/import-excel', [
            'file' => $file,
        ]);

        $responseImport->assertRedirect('/pengguna');
        $responseImport->assertSessionHas('import_result');

        $importResult = session('import_result');
        $this->assertEquals(2, $importResult['success']);
        $this->assertEquals(2, $importResult['failed']);

        $this->assertDatabaseHas('users', ['username' => 'guru_matematika']);
        $this->assertDatabaseHas('users', ['username' => 'pic_jaringan']);

        // Cleanup
        User::whereIn('username', ['guru_matematika', 'pic_jaringan'])->delete();
    }

    public function test_dashboard_admin_menampilkan_widget_ruang_kosong(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Ruang Kosong');
        $response->assertSeeText('Ruangan Kosong');
    }

    public function test_halaman_agenda_menampilkan_tiga_bagian_dan_status_pelaksanaan(): void
    {
        $response = $this->actingAs($this->guru)->get('/agenda');

        $response->assertStatus(200);
        $response->assertSeeText('Agenda Terkini');
        $response->assertSeeText('Agenda Hari Ini');
        $response->assertSeeText('Agenda Yang Akan Datang');
    }

    public function test_kalender_menampilkan_legenda_warna(): void
    {
        $response = $this->get('/kalender-sekolah');

        $response->assertStatus(200);
        $response->assertSeeText('Legenda Warna Kalender');
        $response->assertSeeText('Ada Agenda (1 Event)');
        $response->assertSeeText('Agenda Padat');
        $response->assertSeeText('Hari Penting / Libur');
    }
}
