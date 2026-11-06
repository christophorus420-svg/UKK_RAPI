@extends('layouts.app')

@section('title', 'Ubah Data Ruangan')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
        <div class="mb-6">
            <a href="{{ route('rooms.index') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold inline-flex items-center gap-1 mb-2">
                &larr; Kembali ke Daftar Ruangan
            </a>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Ubah Data Ruangan</h1>
            <p class="text-xs text-slate-500 mt-1">
                Perbarui data fasilitas atau mutasikan penugasan staf PIC penanggung jawab ruangan.
            </p>
        </div>

        <form method="POST" action="{{ route('rooms.update', $room) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="room_name" class="block text-xs font-semibold text-slate-700 mb-1">
                    Nama Ruangan <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="room_name" name="room_name" value="{{ old('room_name', $room->room_name) }}" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="capacity" class="block text-xs font-semibold text-slate-700 mb-1">
                        Kapasitas Orang <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" id="capacity" name="capacity" value="{{ old('capacity', $room->capacity) }}" min="1" max="1000" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                </div>

                <div>
                    <label for="location" class="block text-xs font-semibold text-slate-700 mb-1">
                        Lokasi Gedung & Lantai <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="location" name="location" value="{{ old('location', $room->location) }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                </div>
            </div>

            <div>
                <label for="pic_id" class="block text-xs font-semibold text-slate-700 mb-1">
                    Petugas PIC Penanggung Jawab <span class="text-rose-500">*</span>
                </label>
                <select id="pic_id" name="pic_id" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                    @foreach ($picUsers as $pic)
                        <option value="{{ $pic->id }}" {{ old('pic_id', $room->pic_id) == $pic->id ? 'selected' : '' }}>
                            {{ $pic->fullname }} (Saat ini memegang {{ $pic->rooms_count }} ruangan)
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">
                    Mengubah PIC akan mengalihkan tanggung jawab penanganan kerusakan ruangan ini kepada PIC baru.
                </p>
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $room->is_active) ? 'checked' : '' }}
                        class="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500">
                    <span class="text-xs text-slate-700 font-medium">Ruangan aktif dan dapat dipilih dalam form peminjaman</span>
                </label>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('rooms.index') }}" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
