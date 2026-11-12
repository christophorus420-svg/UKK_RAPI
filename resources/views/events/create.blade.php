@extends('layouts.app')

@section('title', 'Ajukan Peminjaman Ruang')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
        <div class="mb-6">
            <a href="{{ route('events.index') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold inline-flex items-center gap-1 mb-2">
                &larr; Kembali ke Daftar Agenda
            </a>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Formulir Pengajuan Peminjaman Ruang</h1>
            <p class="text-xs text-slate-500 mt-1">
                Pilih satu atau lebih sesi peminjaman. Sistem secara otomatis mendeteksi bentrok jadwal.
            </p>
        </div>

        @include('events._form', [
            'event' => null,
            'rooms' => $rooms,
            'sessions' => $sessions,
            'formAction' => route('events.store'),
            'formMethod' => 'POST',
            'submitLabel' => 'Kirim Pengajuan Agenda',
        ])
    </div>

</div>
@endsection
