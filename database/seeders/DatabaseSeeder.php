<?php

namespace Database\Seeders;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roleAdmin = Role::create(['role_name' => 'admin']);
        $rolePic = Role::create(['role_name' => 'pic']);
        $roleGuru = Role::create(['role_name' => 'guru']);
        $roleGuest = Role::create(['role_name' => 'guest']);

        $defaultPassword = Hash::make('password123');

        $admin = User::create([
            'username' => 'admin',
            'fullname' => 'Drs. H. Ahmad Fauzi, M.Pd (Waka Sarpras)',
            'phone_number' => '6281234567890',
            'password' => $defaultPassword,
            'role_id' => $roleAdmin->id,
        ]);

        $phorusAdmin = User::create([
            'username' => 'phorus_admin',
            'fullname' => 'Phorus Admin',
            'phone_number' => '081234567890',
            'email' => 'christophorus420@gmail.com',
            'password' => Hash::make('admin123'),
            'role_id' => $roleAdmin->id,
        ]);

        $picBudi = User::create([
            'username' => 'pic_budi',
            'fullname' => 'Budi Santoso, S.Kom (Teknisi Lab)',
            'phone_number' => '6285712345678',
            'password' => $defaultPassword,
            'role_id' => $rolePic->id,
        ]);

        $picSiti = User::create([
            'username' => 'pic_siti',
            'fullname' => 'Siti Rahmawati, A.Md (Pengelola Gedung)',
            'phone_number' => '6281987654321',
            'password' => $defaultPassword,
            'role_id' => $rolePic->id,
        ]);

        $guruAndi = User::create([
            'username' => 'guru_andi',
            'fullname' => 'Andi Pratama, S.Pd (Guru Produktif RPL)',
            'phone_number' => '6282133445566',
            'password' => $defaultPassword,
            'role_id' => $roleGuru->id,
        ]);

        $osisSmk = User::create([
            'username' => 'osis_smk',
            'fullname' => 'Pengurus OSIS SMK',
            'phone_number' => '6282299887766',
            'password' => $defaultPassword,
            'role_id' => $roleGuru->id,
        ]);

        $siswaRizky = User::create([
            'username' => 'siswa_rizky',
            'fullname' => 'Rizky Ramadhan (Siswa RPL)',
            'phone_number' => '6281355667788',
            'password' => $defaultPassword,
            'role_id' => $roleGuest->id,
        ]);

        $labRpl1 = Room::create([
            'room_name' => 'Lab Komputer RPL 1',
            'capacity' => 36,
            'location' => 'Gedung B Lantai 2',
            'pic_id' => $picBudi->id,
            'is_active' => true,
        ]);

        $labRpl2 = Room::create([
            'room_name' => 'Lab Komputer RPL 2',
            'capacity' => 36,
            'location' => 'Gedung B Lantai 2',
            'pic_id' => $picBudi->id,
            'is_active' => true,
        ]);

        $aula = Room::create([
            'room_name' => 'Aula Graha Wiyata',
            'capacity' => 300,
            'location' => 'Gedung Utama Lantai 1',
            'pic_id' => $picSiti->id,
            'is_active' => true,
        ]);

        $ruangRapat = Room::create([
            'room_name' => 'Ruang Rapat Guru',
            'capacity' => 40,
            'location' => 'Gedung A Lantai 1',
            'pic_id' => $picSiti->id,
            'is_active' => true,
        ]);

        $bengkelIot = Room::create([
            'room_name' => 'Bengkel Kreatif & IoT',
            'capacity' => 30,
            'location' => 'Gedung C Lantai 1',
            'pic_id' => $picBudi->id,
            'is_active' => true,
        ]);

        $kelasXRpl1 = Room::create([
            'room_name' => 'Ruang Kelas X RPL 1',
            'capacity' => 36,
            'location' => 'Gedung D Lantai 1',
            'pic_id' => $picSiti->id,
            'is_active' => true,
        ]);

        $kelasXiRpl1 = Room::create([
            'room_name' => 'Ruang Kelas XI RPL 1',
            'capacity' => 36,
            'location' => 'Gedung D Lantai 2',
            'pic_id' => $picSiti->id,
            'is_active' => true,
        ]);

        $kelasXiiRpl1 = Room::create([
            'room_name' => 'Ruang Kelas XII RPL 1',
            'capacity' => 36,
            'location' => 'Gedung D Lantai 3',
            'pic_id' => $picSiti->id,
            'is_active' => true,
        ]);

        $perpustakaan = Room::create([
            'room_name' => 'Perpustakaan Utama (Ruang Baca & Referensi)',
            'capacity' => 60,
            'location' => 'Gedung A Lantai 2',
            'pic_id' => $picSiti->id,
            'is_active' => true,
        ]);

        $labBahasa = Room::create([
            'room_name' => 'Laboratorium Bahasa & Multimedia',
            'capacity' => 40,
            'location' => 'Gedung B Lantai 1',
            'pic_id' => $picBudi->id,
            'is_active' => true,
        ]);

        $labIpa = Room::create([
            'room_name' => 'Laboratorium IPA Terapan',
            'capacity' => 36,
            'location' => 'Gedung C Lantai 2',
            'pic_id' => $picBudi->id,
            'is_active' => true,
        ]);

        $ruangBk = Room::create([
            'room_name' => 'Ruang Konseling & Bimbingan Karir (BK)',
            'capacity' => 15,
            'location' => 'Gedung A Lantai 1',
            'pic_id' => $picSiti->id,
            'is_active' => true,
        ]);

        $today = Carbon::today();

        Event::create([
            'room_id' => $labRpl1->id,
            'pic_id' => $guruAndi->id,
            'event_name' => 'Pelatihan Cloud Computing & DevOps',
            'date' => $today->copy()->addDays(1)->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '11:30:00',
            'status' => 'approved',
            'approved_by' => $admin->id,
            'move_reason' => null,
        ]);

        Event::create([
            'room_id' => $ruangRapat->id,
            'pic_id' => $admin->id,
            'event_name' => 'Rapat Koordinasi Persiapan UKK',
            'date' => $today->copy()->addDays(2)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'status' => 'approved',
            'approved_by' => $admin->id,
            'move_reason' => null,
        ]);

        Event::create([
            'room_id' => $aula->id,
            'pic_id' => $osisSmk->id,
            'event_name' => 'Latihan Geladi Bersih Pentas Seni',
            'date' => $today->copy()->addDays(3)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '16:00:00',
            'status' => 'pending',
            'approved_by' => null,
            'move_reason' => null,
        ]);

        Complaint::create([
            'room_id' => $labRpl1->id,
            'reporter_id' => $guruAndi->id,
            'description' => 'AC unit 2 bocor dan menetes ke dekat stopkontak deret C. Khawatir korsleting saat praktikum.',
            'photo_path' => null,
            'status_repair' => 'Belum Diperbaiki',
            'handler_id' => $picBudi->id,
            'reported_at' => Carbon::now()->subDays(3),
        ]);

        Complaint::create([
            'room_id' => $ruangRapat->id,
            'reporter_id' => $admin->id,
            'description' => 'Proyektor mati mendadak saat rapat komite. Lampu indikator berkedip merah terus.',
            'photo_path' => null,
            'status_repair' => 'Menunggu Spare Part',
            'handler_id' => $picSiti->id,
            'reported_at' => Carbon::now()->subDays(1),
        ]);

        Complaint::create([
            'room_id' => $labRpl2->id,
            'reporter_id' => $guruAndi->id,
            'description' => 'Kabel LAN PC nomor 12 putus terjepit kaki meja.',
            'photo_path' => null,
            'status_repair' => 'Sudah Diperbaiki',
            'handler_id' => $picBudi->id,
            'reported_at' => Carbon::now()->subDays(4),
        ]);
    }
}
