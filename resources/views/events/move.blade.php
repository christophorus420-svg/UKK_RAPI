@extends('layouts.app')

@section('title', 'Pemindahan Ruangan (Overriding Waka)')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
        <div class="mb-6">
            <a href="{{ route('events.index') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold inline-flex items-center gap-1 mb-2">
                &larr; Kembali ke Daftar Agenda
            </a>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200 mb-2">
                <span>Hak Veto Waka Sarpras</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Formulir Pemindahan Ruangan Terpinjam</h1>
            <p class="text-xs text-slate-500 mt-1">
                Waka/Admin memiliki wewenang memindahkan ruangan kegiatan yang telah diajukan ke fasilitas alternatif jika terdapat kebutuhan mendesak sekolah.
            </p>
        </div>

        <!-- Kartu Detail Agenda Saat Ini -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs mb-6 space-y-2">
            <div class="font-bold text-slate-900 text-sm">{{ $event->event_name }}</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-600 pt-1">
                <div><strong>Pemohon:</strong> {{ $event->user->fullname }}</div>
                <div><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($event->date)->translatedFormat('l, d F Y') }}</div>
                <div><strong>Waktu:</strong> {{ substr($event->start_time, 0, 5) }} - {{ substr($event->end_time, 0, 5) }} WIB</div>
                <div><strong>Ruang Saat Ini:</strong> <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 font-bold border border-rose-200">{{ $event->room->room_name }}</span></div>
            </div>
        </div>

        <form method="POST" action="{{ route('events.move', $event) }}" class="space-y-4">
            @csrf

            <!-- Pilih Ruang Baru -->
            <div>
                <label for="new_room_id" class="block text-xs font-semibold text-slate-700 mb-1">
                    Pilih Ruangan Baru / Alternatif <span class="text-rose-500">*</span>
                </label>
                <select id="new_room_id" name="new_room_id" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                    <option value="">-- Pilih Ruangan Pengganti --</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" {{ old('new_room_id') == $room->id ? 'selected' : '' }}>
                            {{ $room->room_name }} (Kapasitas: {{ $room->capacity }} orang • {{ $room->location }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Alasan Pemindahan (Wajib Sesuai Rubrik UKK Halaman 4 & 5) -->
            <div>
                <label for="move_reason" class="block text-xs font-semibold text-slate-700 mb-1">
                    Alasan Pemindahan Ruangan <span class="text-rose-500">*</span>
                </label>
                <textarea id="move_reason" name="move_reason" rows="3" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                    placeholder="Tuliskan alasan pemindahan secara jelas, misal: Ruangan Lab 1 dialihkan darurat untuk Asesmen Nasional Berbasis Komputer (ANBK) dinas pendidikan.">{{ old('move_reason') }}</textarea>
                <p class="text-[11px] text-slate-400 mt-1">
                    Alasan ini akan otomatis dicatat pada sistem dan dimunculkan sebagai pop-up notifikasi bagi guru pemohon.
                </p>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('events.index') }}" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Batal
                </a>
                <button type="submit"
                    class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                    Konfirmasi & Pindahkan Ruangan
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
