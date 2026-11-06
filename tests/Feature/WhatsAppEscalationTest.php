<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class WhatsAppEscalationTest extends TestCase
{
    protected User $admin;
    protected User $picBudi;
    protected User $guru;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'admin')->first();
        $this->picBudi = User::where('username', 'pic_budi')->first();
        $this->guru = User::where('username', 'guru_andi')->first();
        $this->room = Room::where('room_name', 'Lab Komputer RPL 1')->first();
    }

    public function test_nomor_telepon_diubah_ke_format_internasional_whatsapp(): void
    {
        $user1 = new User(['phone_number' => '085712345678']);
        $this->assertEquals('6285712345678', $user1->whatsapp_number);

        $user2 = new User(['phone_number' => '+62 812-3456-7890']);
        $this->assertEquals('6281234567890', $user2->whatsapp_number);

        $user3 = new User(['phone_number' => '89911223344']);
        $this->assertEquals('6289911223344', $user3->whatsapp_number);
    }

    public function test_url_eskalasi_whatsapp_mengarah_ke_nomor_pic_ruangan(): void
    {
        $complaint = Complaint::create([
            'room_id' => $this->room->id,
            'reporter_id' => $this->guru->id,
            'description' => 'AC bocor menetes ke stopkontak.',
            'status_repair' => 'Belum Diperbaiki',
            'handler_id' => $this->picBudi->id,
            'reported_at' => Carbon::now()->subDays(3),
        ]);

        $url = $complaint->whatsapp_escalation_url;

        $this->assertStringStartsWith('https://wa.me/' . $this->picBudi->whatsapp_number, $url);
        $this->assertStringContainsString('text=', $url);

        // Dekode pesan teks URL
        $parsedUrl = parse_url($url);
        parse_str($parsedUrl['query'], $query);
        $pesanTeks = $query['text'];

        $this->assertStringContainsString($this->room->room_name, $pesanTeks);
        $this->assertStringContainsString('AC bocor menetes ke stopkontak.', $pesanTeks);
        $this->assertStringContainsString($this->picBudi->fullname, $pesanTeks);

        // Bersihkan data uji
        $complaint->delete();
    }

    public function test_deteksi_keterlambatan_is_overdue_berfungsi_benar(): void
    {
        // 1. Laporan 3 hari lalu belum diperbaiki -> OVERDUE = TRUE
        $c1 = new Complaint([
            'status_repair' => 'Belum Diperbaiki',
            'reported_at' => Carbon::now()->subDays(3),
        ]);
        $this->assertTrue($c1->isOverdue());

        // 2. Laporan baru beberapa jam lalu -> OVERDUE = FALSE
        $c2 = new Complaint([
            'status_repair' => 'Belum Diperbaiki',
            'reported_at' => Carbon::now(),
        ]);
        $this->assertFalse($c2->isOverdue());

        // 3. Laporan 3 hari lalu tetapi SUDAH DIPERBAIKI -> OVERDUE = FALSE
        $c3 = new Complaint([
            'status_repair' => 'Sudah Diperbaiki',
            'reported_at' => Carbon::now()->subDays(3),
        ]);
        $this->assertFalse($c3->isOverdue());
    }

    public function test_tombol_whatsapp_muncul_pada_dashboard_waka(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Eskalasi Kerusakan ke PIC');
        $response->assertSee('https://wa.me/');
    }

    public function test_tombol_whatsapp_muncul_pada_halaman_pengaduan_untuk_waka(): void
    {
        $response = $this->actingAs($this->admin)->get('/pengaduan');
        $response->assertStatus(200);
        $response->assertSee('Peringatkan PIC via WhatsApp');
        $response->assertSee('https://wa.me/');
    }

    public function test_guru_tidak_melihat_tombol_eskalasi_whatsapp(): void
    {
        $response = $this->actingAs($this->guru)->get('/pengaduan');
        $response->assertStatus(200);
        $response->assertDontSee('Peringatkan PIC via WhatsApp');
    }
}
