<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\ComplaintApiController;
use App\Http\Controllers\Api\EventApiController;
use App\Http\Controllers\Api\RoomApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API Routes - RAPI-Sekolah (Mobile Flutter & Client Integration)
|--------------------------------------------------------------------------
| Seluruh endpoint mengembalikan respons JSON standar.
| Autentikasi menggunakan Personal Access Token (Bearer Token).
*/

// --- ROUTE PUBLIK (Tidak memerlukan Token) ---
Route::post('/login', [AuthApiController::class, 'login']);
Route::post('/forgot-password', [AuthApiController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthApiController::class, 'resetPassword']);

// Endpoint Ruangan (Katalog & Status Ruangan Sekolah)
Route::get('/ruangan', [RoomApiController::class, 'index']);
Route::get('/ruangan/{id}', [RoomApiController::class, 'show']);

// Endpoint Agenda Resmi (Jadwal Terbuka Publik)
Route::get('/agenda', [EventApiController::class, 'index']);
Route::get('/agenda/{id}', [EventApiController::class, 'show']);


// --- ROUTE TERPROTEKSI (Wajib Header: Authorization: Bearer <token>) ---
Route::middleware('auth.api')->group(function () {

    // Profil Pengguna & Logout
    Route::get('/me', [AuthApiController::class, 'me']);
    Route::get('/user', [AuthApiController::class, 'me']);
    Route::post('/logout', [AuthApiController::class, 'logout']);

    // Pengajuan Agenda (Dengan Validasi Anti-Bentrok)
    Route::post('/agenda', [EventApiController::class, 'store']);

    // Modul Pengaduan Kerusakan Sarpras
    Route::get('/pengaduan', [ComplaintApiController::class, 'index']);
    Route::post('/pengaduan', [ComplaintApiController::class, 'store']);
    Route::patch('/pengaduan/{id}/status', [ComplaintApiController::class, 'updateStatus']);
    Route::delete('/pengaduan/{id}', [ComplaintApiController::class, 'destroy']);

});
