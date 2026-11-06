@extends('layouts.app')

@section('title', 'Tambah Ruangan Baru')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
        <div class="mb-6">
            <a href="{{ route('rooms.index') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold inline-flex items-center gap-1 mb-2">
                &larr; Kembali ke Daftar Ruangan
            </a>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Tambah Ruangan & Sarana Baru</h1>
            <p class="text-xs text-slate-500 mt-1">
                Masukkan identitas ruangan dan tetapkan staf PIC yang bertanggung jawab atas pemeliharaannya.
            </p>
        </div>

        <form method="POST" action="{{ route('rooms.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="room_name" class="block text-xs font-semibold text-slate-700 mb-1">
                    Nama Ruangan <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="room_name" name="room_name" value="{{ old('room_name') }}" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                    placeholder="Contoh: Lab Jaringan Komputer, Studio Musik, Ruang Teori 3">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="capacity" class="block text-xs font-semibold text-slate-700 mb-1">
                        Kapasitas Orang <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" id="capacity" name="capacity" value="{{ old('capacity', 36) }}" min="1" max="1000" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                        placeholder="Contoh: 36">
                </div>

                <div>
                    <label for="location" class="block text-xs font-semibold text-slate-700 mb-1">
                        Lokasi Gedung & Lantai <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="location" name="location" value="{{ old('location') }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                        placeholder="Contoh: Gedung B Lantai 2">
                </div>
            </div>

            <div>
                <label for="pic_id" class="block text-xs font-semibold text-slate-700 mb-1">
                    Petugas PIC Penanggung Jawab <span class="text-rose-500">*</span>
                </label>
                <select id="pic_id" name="pic_id" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                    <option value="">-- Pilih Staf PIC Ruangan --</option>
                    @foreach ($picUsers as $pic)
                        <option value="{{ $pic->id }}" {{ old('pic_id') == $pic->id ? 'selected' : '' }}>
                            {{ $pic->fullname }} (Saat ini memegang {{ $pic->rooms_count }} ruangan)
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">
                    Sesuai aturan UKK: Satu staf PIC dapat didelegasikan memegang beberapa ruangan sekaligus (One-to-Many).
                </p>
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                        class="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500">
                    <span class="text-xs text-slate-700 font-medium">Ruangan langsung aktif dan siap digunakan untuk peminjaman</span>
                </label>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('rooms.index') }}" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                    Simpan Ruangan Baru
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
