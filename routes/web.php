<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingSessionController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\PublicCalendarController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

// Halaman awal mengarahkan ke dashboard jika login, atau ke kalender publik jika belum login
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('calendar.public');
});

// Kalender Publik (Dapat diakses tanpa login - Guest/Siswa/Umum)
Route::get('/kalender-sekolah', [PublicCalendarController::class, 'index'])->name('calendar.public');

// Autentikasi Pengguna & Lupa Password
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/lupa-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
Route::post('/lupa-password', [ForgotPasswordController::class, 'sendResetToken'])->name('password.send-token');
Route::get('/reset-password/{token?}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');

use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;

// Rute Terproteksi Login
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Modul Ruangan
    Route::get('/ruangan', [RoomController::class, 'index'])->name('rooms.index');

    Route::middleware('role:admin')->group(function () {
        Route::get('/ruangan/tambah', [RoomController::class, 'create'])->name('rooms.create');
        Route::post('/ruangan', [RoomController::class, 'store'])->name('rooms.store');
        Route::get('/ruangan/{room}/edit', [RoomController::class, 'edit'])->name('rooms.edit');
        Route::put('/ruangan/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/ruangan/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
        Route::patch('/ruangan/{room}/toggle', [RoomController::class, 'toggleStatus'])->name('rooms.toggle');
    });

    // Modul Agenda & Peminjaman
    Route::get('/agenda', [EventController::class, 'index'])->name('events.index');
    Route::get('/agenda/ajukan', [EventController::class, 'create'])->name('events.create');
    Route::post('/agenda', [EventController::class, 'store'])->name('events.store');
    Route::get('/agenda/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
    Route::put('/agenda/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('/agenda/{event}', [EventController::class, 'destroy'])->name('events.destroy');
    Route::get('/api/cek-ketersediaan-ruang', [EventController::class, 'checkAvailability'])->name('events.check-availability');

    Route::middleware('role:admin')->group(function () {
        Route::post('/agenda/{event}/setujui', [EventController::class, 'approve'])->name('events.approve');
        Route::post('/agenda/{event}/tolak', [EventController::class, 'reject'])->name('events.reject');
        Route::get('/agenda/{event}/pindah-ruang', [EventController::class, 'moveRoomForm'])->name('events.move-form');
        Route::post('/agenda/{event}/pindah-ruang', [EventController::class, 'moveRoom'])->name('events.move');
    });

    // Modul Pengaduan Kerusakan Sarpras
    Route::get('/pengaduan', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/pengaduan/lapor', [ComplaintController::class, 'create'])->name('complaints.create');
    Route::post('/pengaduan', [ComplaintController::class, 'store'])->name('complaints.store');
    Route::patch('/pengaduan/{complaint}/status', [ComplaintController::class, 'updateStatus'])->name('complaints.update-status');
    Route::delete('/pengaduan/{complaint}', [ComplaintController::class, 'destroy'])->name('complaints.destroy');

    // Modul Rekap Laporan (Admin / Waka Sarpras)
    Route::middleware('role:admin')->group(function () {
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
        Route::get('/laporan/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv');

        // Modul Pengaturan Waktu Sesi Peminjaman
        Route::put('/sesi/{booking_session}', [BookingSessionController::class, 'update'])->name('sessions.update');
        Route::patch('/sesi/{booking_session}/toggle', [BookingSessionController::class, 'toggleStatus'])->name('sessions.toggle');

        // Modul Manajemen Pengguna (Waka / Admin)
        Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
        Route::get('/pengguna/template-excel', [UserController::class, 'downloadTemplate'])->name('users.template-excel');
        Route::post('/pengguna/import-excel', [UserController::class, 'importExcel'])->name('users.import-excel');
        Route::get('/pengguna/tambah', [UserController::class, 'create'])->name('users.create');
        Route::post('/pengguna', [UserController::class, 'store'])->name('users.store');
        Route::get('/pengguna/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/pengguna/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/pengguna/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
