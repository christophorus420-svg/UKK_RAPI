<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    protected User $admin;
    protected User $guru;
    protected User $pic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'admin')->first();
        $this->guru = User::where('username', 'guru_andi')->first();
        $this->pic = User::where('username', 'pic_budi')->first();
    }

    protected function tearDown(): void
    {
        // Bersihkan data user testing
        User::where('username', 'like', 'user_test_%')->delete();

        parent::tearDown();
    }

    public function test_admin_dapat_mengakses_halaman_daftar_pengguna(): void
    {
        $response = $this->actingAs($this->admin)->get('/pengguna');

        $response->assertStatus(200);
        $response->assertSeeText('Kelola Pengguna Sistem');
        $response->assertSeeText('Tambah Pengguna Baru');
        $response->assertSeeText($this->admin->fullname);
    }

    public function test_guru_dan_pic_dilarang_mengakses_manajemen_pengguna(): void
    {
        // Guru ditolak dan diarahkan ke dashboard
        $responseGuru = $this->actingAs($this->guru)->get('/pengguna');
        $responseGuru->assertRedirect('/dashboard');
        $responseGuru->assertSessionHas('error');

        // PIC ditolak dan diarahkan ke dashboard
        $responsePic = $this->actingAs($this->pic)->get('/pengguna');
        $responsePic->assertRedirect('/dashboard');
        $responsePic->assertSessionHas('error');
    }

    public function test_admin_dapat_mengakses_halaman_tambah_pengguna(): void
    {
        $response = $this->actingAs($this->admin)->get('/pengguna/tambah');

        $response->assertStatus(200);
        $response->assertSeeText('Tambah Pengguna Baru');
        $response->assertSee('Simpan Pengguna Baru');
    }

    public function test_admin_dapat_menambah_pengguna_baru_dengan_sukses(): void
    {
        $roleGuru = Role::where('role_name', 'guru')->first();

        $response = $this->actingAs($this->admin)->post('/pengguna', [
            'username' => 'user_test_guru1',
            'fullname' => 'Dewi Lestari, S.Pd',
            'email' => 'dewi.lestari.test@sekolah.sch.id',
            'phone_number' => '081299881122',
            'role_id' => $roleGuru->id,
            'password' => 'PasswordGuru123!',
            'password_confirmation' => 'PasswordGuru123!',
        ]);

        $response->assertRedirect('/pengguna');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'username' => 'user_test_guru1',
            'email' => 'dewi.lestari.test@sekolah.sch.id',
            'fullname' => 'Dewi Lestari, S.Pd',
            'role_id' => $roleGuru->id,
        ]);

        $createdUser = User::where('username', 'user_test_guru1')->first();
        $this->assertTrue(Hash::check('PasswordGuru123!', $createdUser->password));
    }

    public function test_validasi_gagal_jika_username_atau_email_sudah_digunakan(): void
    {
        $roleGuru = Role::where('role_name', 'guru')->first();

        $response = $this->actingAs($this->admin)->post('/pengguna', [
            'username' => 'guru_andi', // Sudah ada
            'fullname' => 'Andi Kembaran',
            'email' => 'guru_andi@sekolah.sch.id', // Sudah ada
            'role_id' => $roleGuru->id,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['username', 'email']);
    }

    public function test_validasi_gagal_jika_konfirmasi_password_tidak_cocok(): void
    {
        $roleGuru = Role::where('role_name', 'guru')->first();

        $response = $this->actingAs($this->admin)->post('/pengguna', [
            'username' => 'user_test_mismatch',
            'fullname' => 'Test User Mismatch',
            'email' => 'test.mismatch@sekolah.sch.id',
            'role_id' => $roleGuru->id,
            'password' => 'Password123!',
            'password_confirmation' => 'PasswordBeda456!',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['username' => 'user_test_mismatch']);
    }

    public function test_admin_dapat_mengubah_data_pengguna(): void
    {
        $roleGuest = Role::where('role_name', 'guest')->first();
        $user = User::create([
            'username' => 'user_test_edit',
            'fullname' => 'Nama Sebelum Diedit',
            'email' => 'sebelum.edit@sekolah.sch.id',
            'phone_number' => '081111111111',
            'password' => Hash::make('password123'),
            'role_id' => $roleGuest->id,
        ]);

        $roleGuru = Role::where('role_name', 'guru')->first();

        $response = $this->actingAs($this->admin)->put("/pengguna/{$user->id}", [
            'username' => 'user_test_edit',
            'fullname' => 'Nama Setelah Diperbarui',
            'email' => 'setelah.edit@sekolah.sch.id',
            'phone_number' => '082222222222',
            'role_id' => $roleGuru->id,
        ]);

        $response->assertRedirect('/pengguna');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'fullname' => 'Nama Setelah Diperbarui',
            'email' => 'setelah.edit@sekolah.sch.id',
            'role_id' => $roleGuru->id,
        ]);
    }

    public function test_admin_dapat_menghapus_pengguna_tanpa_keterkaitan_riwayat(): void
    {
        $roleGuest = Role::where('role_name', 'guest')->first();
        $user = User::create([
            'username' => 'user_test_delete',
            'fullname' => 'Pengguna Hapus',
            'email' => 'hapus.me@sekolah.sch.id',
            'password' => Hash::make('password123'),
            'role_id' => $roleGuest->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/pengguna/{$user->id}");

        $response->assertRedirect('/pengguna');
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_tidak_dapat_menghapus_akun_sendiri(): void
    {
        $response = $this->actingAs($this->admin)->delete("/pengguna/{$this->admin->id}");

        $response->assertRedirect('/pengguna');
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_admin_dilarang_menghapus_user_yang_memegang_ruangan_pic(): void
    {
        // pic_budi memegang Lab Komputer RPL 1 & 2
        $response = $this->actingAs($this->admin)->delete("/pengguna/{$this->pic->id}");

        $response->assertRedirect('/pengguna');
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->pic->id]);
    }
}
