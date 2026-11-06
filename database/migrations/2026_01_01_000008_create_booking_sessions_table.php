<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_name', 100);
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed data 13 sesi standar sekolah (45 menit per sesi, 07:00 - 16:45 WIB)
        $sessions = [
            ['session_name' => 'Sesi 1 (Jam Ke-1)',  'start_time' => '07:00:00', 'end_time' => '07:45:00'],
            ['session_name' => 'Sesi 2 (Jam Ke-2)',  'start_time' => '07:45:00', 'end_time' => '08:30:00'],
            ['session_name' => 'Sesi 3 (Jam Ke-3)',  'start_time' => '08:30:00', 'end_time' => '09:15:00'],
            ['session_name' => 'Sesi 4 (Jam Ke-4)',  'start_time' => '09:15:00', 'end_time' => '10:00:00'],
            ['session_name' => 'Sesi 5 (Jam Ke-5)',  'start_time' => '10:00:00', 'end_time' => '10:45:00'],
            ['session_name' => 'Sesi 6 (Jam Ke-6)',  'start_time' => '10:45:00', 'end_time' => '11:30:00'],
            ['session_name' => 'Sesi 7 (Jam Ke-7)',  'start_time' => '11:30:00', 'end_time' => '12:15:00'],
            ['session_name' => 'Sesi 8 (Jam Ke-8)',  'start_time' => '12:15:00', 'end_time' => '13:00:00'],
            ['session_name' => 'Sesi 9 (Jam Ke-9)',  'start_time' => '13:00:00', 'end_time' => '13:45:00'],
            ['session_name' => 'Sesi 10 (Jam Ke-10)', 'start_time' => '13:45:00', 'end_time' => '14:30:00'],
            ['session_name' => 'Sesi 11 (Jam Ke-11)', 'start_time' => '14:30:00', 'end_time' => '15:15:00'],
            ['session_name' => 'Sesi 12 (Jam Ke-12)', 'start_time' => '15:15:00', 'end_time' => '16:00:00'],
            ['session_name' => 'Sesi 13 (Jam Ke-13)', 'start_time' => '16:00:00', 'end_time' => '16:45:00'],
        ];

        foreach ($sessions as $s) {
            DB::table('booking_sessions')->insert([
                'session_name' => $s['session_name'],
                'start_time' => $s['start_time'],
                'end_time' => $s['end_time'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_sessions');
    }
};
