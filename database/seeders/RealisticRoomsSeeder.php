<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;

class RealisticRoomsSeeder extends Seeder
{
    public function run(): void
    {
        $picBudi = User::where('username', 'pic_budi')->first();
        $picSiti = User::where('username', 'pic_siti')->first();

        if (!$picBudi || !$picSiti) {
            return;
        }

        $rooms = [
            [
                'room_name' => 'Ruang Kelas X RPL 1',
                'capacity' => 36,
                'location' => 'Gedung D Lantai 1',
                'pic_id' => $picSiti->id,
                'is_active' => true,
            ],
            [
                'room_name' => 'Ruang Kelas XI RPL 1',
                'capacity' => 36,
                'location' => 'Gedung D Lantai 2',
                'pic_id' => $picSiti->id,
                'is_active' => true,
            ],
            [
                'room_name' => 'Ruang Kelas XII RPL 1',
                'capacity' => 36,
                'location' => 'Gedung D Lantai 3',
                'pic_id' => $picSiti->id,
                'is_active' => true,
            ],
            [
                'room_name' => 'Perpustakaan Utama (Ruang Baca & Referensi)',
                'capacity' => 60,
                'location' => 'Gedung A Lantai 2',
                'pic_id' => $picSiti->id,
                'is_active' => true,
            ],
            [
                'room_name' => 'Laboratorium Bahasa & Multimedia',
                'capacity' => 40,
                'location' => 'Gedung B Lantai 1',
                'pic_id' => $picBudi->id,
                'is_active' => true,
            ],
            [
                'room_name' => 'Laboratorium IPA Terapan',
                'capacity' => 36,
                'location' => 'Gedung C Lantai 2',
                'pic_id' => $picBudi->id,
                'is_active' => true,
            ],
            [
                'room_name' => 'Ruang Konseling & Bimbingan Karir (BK)',
                'capacity' => 15,
                'location' => 'Gedung A Lantai 1',
                'pic_id' => $picSiti->id,
                'is_active' => true,
            ],
        ];

        foreach ($rooms as $data) {
            Room::firstOrCreate(['room_name' => $data['room_name']], $data);
        }
    }
}

