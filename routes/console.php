<?php

use App\Models\Complaint;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('complaints:purge-resolved', function () {
    $count = Complaint::purgeResolvedOverdue();
    $this->info("Berhasil membersihkan {$count} pengaduan yang telah berstatus 'Sudah Diperbaiki' melewati 24 jam.");
})->purpose('Otomatis menghapus pengaduan berstatus Sudah Diperbaiki setelah 24 jam');

Schedule::command('complaints:purge-resolved')->hourly();

