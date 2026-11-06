<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiTest extends TestCase
{
    protected function tearDown(): void
    {
        // Bersihkan data dummy setelah pengujian
        Event::where('event_name', 'like', 'TEST_API_%')->delete();
        Complaint::where('description', 'like', 'TEST_API_%')->delete();
        Room::where('room_name', 'like', 'TEST_API_ROOM_%')->delete();
        ApiToken::where('name', 'like', 'test_%')->delete();

        parent::tearDown();
    }

    public function test_api_login_berhasil_dan_mengembalikan_bearer_token(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'guru_andi',
            'password' => 'password123',
            'device_name' => 'test_flutter_device',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'token',
            'user' => [
                'id',
                'username',
                'fullname',
                'role',
            ],
        ]);
        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_api_login_gagal_jika_password_salah(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'guru_andi',
            'password' => 'passwordsalah123',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_api_me_menolak_request_tanpa_token(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_api_me_berhasil_dengan_bearer_token(): void
    {
        $user = User::where('username', 'guru_andi')->first();
        $token = $user->createToken('test_token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'user' => [
                'username' => 'guru_andi',
                'role' => 'guru',
            ],
        ]);
    }

    public function test_api_katalog_ruangan_publik_dapat_diakses(): void
    {
        $response = $this->getJson('/api/ruangan');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'total',
            'data' => [
                '*' => ['id', 'room_name', 'location', 'capacity'],
            ],
        ]);
    }

    public function test_api_katalog_agenda_publik_dapat_diakses(): void
    {
        $response = $this->getJson('/api/agenda');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'total',
            'data',
        ]);
    }

    public function test_api_pengajuan_agenda_dan_validasi_anti_bentrok(): void
    {
        $guru = User::where('username', 'guru_andi')->first();
        $token = $guru->createToken('test_token');

        $room = Room::create([
            'room_name' => 'TEST_API_ROOM_AGENDA',
            'location' => 'Gedung Test',
            'capacity' => 30,
            'pic_id' => $guru->id,
            'is_active' => true,
        ]);

        $tomorrow = Carbon::tomorrow()->toDateString();

        // 1. Pengajuan pertama sukses
        $resFirst = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/agenda', [
                'room_id' => $room->id,
                'event_name' => 'TEST_API_AGENDA_VALID_1',
                'date' => $tomorrow,
                'start_time' => '08:00',
                'end_time' => '10:00',
            ]);

        $resFirst->assertStatus(201);
        $resFirst->assertJson([
            'success' => true,
        ]);

        // 2. Pengajuan kedua pada jam bertabrakan (09:00 - 11:00) HARUS DITOLAK 422
        $resConflict = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/agenda', [
                'room_id' => $room->id,
                'event_name' => 'TEST_API_AGENDA_BENTROK',
                'date' => $tomorrow,
                'start_time' => '09:00',
                'end_time' => '11:00',
            ]);

        $resConflict->assertStatus(422);
        $resConflict->assertJson([
            'success' => false,
        ]);
        $this->assertNotEmpty($resConflict->json('conflicts'));
    }

    public function test_api_pic_dapat_mengubah_status_pengaduan_sarpras(): void
    {
        $pic = User::where('username', 'pic_budi')->first();
        $token = $pic->createToken('test_token');

        // Buat ruangan milik PIC Budi
        $room = Room::create([
            'room_name' => 'TEST_API_ROOM_LAB',
            'location' => 'Lab Komputer',
            'capacity' => 36,
            'pic_id' => $pic->id,
            'is_active' => true,
        ]);

        $complaint = Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $pic->id,
            'description' => 'TEST_API_Mouse komputer rusak tombol kiri macet.',
            'status_repair' => 'Belum Diperbaiki',
            'reported_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/pengaduan/{$complaint->id}/status", [
                'status' => 'Menunggu Spare Part',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status_repair' => 'Menunggu Spare Part',
        ]);
    }

    public function test_api_admin_dilarang_mengubah_status_pengaduan_sarpras(): void
    {
        $admin = User::where('username', 'admin')->first();
        $token = $admin->createToken('admin_test_token');

        $complaint = Complaint::first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/pengaduan/{$complaint->id}/status", [
                'status' => 'Sudah Diperbaiki',
            ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_api_logout_mencabut_token(): void
    {
        $user = User::where('username', 'guru_andi')->first();
        $token = $user->createToken('test_token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // Coba akses lagi dengan token yang sama -> harus 401
        $resCheck = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me');

        $resCheck->assertStatus(401);
    }

    public function test_api_guru_dapat_membuat_pengaduan_sarpras_dengan_foto(): void
    {
        Storage::fake('public');
        $guru = User::where('username', 'guru_andi')->first();
        $token = $guru->createToken('test_token');
        $room = Room::first();

        $file = UploadedFile::fake()->image('kerusakan.jpg', 600, 600);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post('/api/pengaduan', [
                'room_id' => $room->id,
                'description' => 'kabelnya komputer yang digunakan putus',
                'photo' => $file,
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_api_dapat_menghapus_pengaduan_secara_manual(): void
    {
        $guru = User::where('username', 'guru_andi')->first();
        $token = $guru->createToken('test_token');
        $room = Room::first();

        $complaint = Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $guru->id,
            'description' => 'Colokan kabel proyektor patah.',
            'status_repair' => 'Belum Diperbaiki',
            'reported_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/pengaduan/{$complaint->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Laporan pengaduan berhasil dihapus dari sistem.',
        ]);

        $this->assertDatabaseMissing('complaints', ['id' => $complaint->id]);
    }
}
