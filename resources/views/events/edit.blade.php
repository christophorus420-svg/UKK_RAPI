@extends('layouts.app')

@section('title', 'Edit Peminjaman & Agenda')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
        <div class="mb-6">
            <a href="{{ route('events.index') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold inline-flex items-center gap-1 mb-2">
                &larr; Kembali ke Daftar Agenda
            </a>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Edit Peminjaman Ruang &amp; Agenda</h1>
            <p class="text-xs text-slate-500 mt-1">
                Perbarui nama agenda, ruangan, tanggal, atau pilihan sesi kegiatan. Sistem tetap mengecek bentrok jadwal secara otomatis.
            </p>
        </div>

        @include('events._form', [
            'event' => $event,
            'rooms' => $rooms,
            'sessions' => $sessions,
            'formAction' => route('events.update', $event),
            'formMethod' => 'PUT',
            'submitLabel' => 'Simpan Perubahan Agenda',
        ])
    </div>

</div>
@endsection

