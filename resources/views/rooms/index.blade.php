@extends('layouts.app')

@section('title', 'Master Data Ruangan')

@section('content')
<div class="space-y-6">

    <!-- Header & Action -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 mb-2">
                <span>Sarana & Prasarana Sekolah</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Daftar Ruangan & Fasilitas</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Pemetaan sarana ruang sekolah dan pembagian tanggung jawab PIC Ruang (One-to-Many).
            </p>
        </div>

        @if (Auth::user()->isAdmin())
            <a href="{{ route('rooms.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold transition shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Tambah Ruangan Baru</span>
            </a>
        @endif
    </div>

    <!-- Ringkasan Delegasi PIC (Konsep One-to-Many Sesuai Rubrik UKK) -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
        <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">
            Pemetaan Delegasi PIC Ruangan (One-to-Many)
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach ($picUsers as $pic)
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs flex items-center justify-between">
                    <div>
                        <div class="font-bold text-slate-900">{{ $pic->fullname }}</div>
                        <div class="text-slate-500 mt-0.5">Telp: {{ $pic->phone_number ?? '-' }}</div>
                    </div>
                    <span class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-lg font-semibold text-xs border border-amber-200">
                        {{ $pic->rooms_count }} Ruangan
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Filter Pencarian & Status -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('rooms.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <div class="sm:col-span-5">
                <input type="text" name="q" value="{{ request('q') }}"
                    placeholder="Cari nama ruangan atau lokasi..."
                    class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
            </div>
            <div class="sm:col-span-3">
                <select name="pic_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white">
                    <option value="">Semua PIC Ruang</option>
                    @foreach ($picUsers as $pic)
                        <option value="{{ $pic->id }}" {{ request('pic_id') == $pic->id ? 'selected' : '' }}>
                            {{ $pic->fullname }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <select name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="sm:col-span-2 flex gap-2">
                <button type="submit" class="flex-1 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold transition">
                    Filter
                </button>
                @if (request()->hasAny(['q', 'pic_id', 'status']))
                    <a href="{{ route('rooms.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition flex items-center justify-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Grid Kartu Ruangan -->
    @if ($rooms->isEmpty())
        <div class="bg-white rounded-2xl p-12 text-center text-xs text-slate-400 border border-dashed border-slate-200">
            Tidak ada ruangan yang sesuai dengan filter pencarian Anda.
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($rooms as $room)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs hover:shadow-sm transition p-5 flex flex-col justify-between">
                    <div>
                        <!-- Header Card Ruang -->
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold mb-1
                                    {{ $room->is_active ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $room->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    <span>{{ $room->is_active ? 'Siap Digunakan' : 'Dinonaktifkan' }}</span>
                                </span>
                                <h3 class="text-base font-bold text-slate-900 leading-snug">{{ $room->room_name }}</h3>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-xs font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded-lg">
                                    {{ $room->capacity }} Kursi
                                </span>
                            </div>
                        </div>

                        <!-- Info Lokasi -->
                        <div class="mt-3 text-xs text-slate-600 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>{{ $room->location }}</span>
                        </div>

                        <!-- Info PIC -->
                        <div class="mt-3 p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                            <div class="text-[11px] text-slate-400 font-semibold uppercase">Petugas PIC Ruangan</div>
                            <div class="font-bold text-slate-800 mt-0.5">{{ $room->pic->fullname }}</div>
                            <div class="text-slate-500 mt-0.5">WA: {{ $room->pic->phone_number ?? '-' }}</div>
                        </div>

                        <!-- Counter Relasi -->
                        <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500">
                            <span>{{ $room->events_count }} Riwayat Agenda</span>
                            <span>{{ $room->complaints_count }} Laporan Kerusakan</span>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    @if (Auth::user()->isAdmin())
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                            <!-- Toggle Status Aktif/Nonaktif -->
                            <form method="POST" action="{{ route('rooms.toggle', $room) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-xs px-2.5 py-1.5 rounded-lg border font-medium transition
                                    {{ $room->is_active ? 'border-slate-200 text-slate-600 hover:bg-slate-100' : 'border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                                    {{ $room->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>

                            <div class="flex items-center gap-1.5">
                                @if ($room->is_active)
                                    <a href="{{ route('events.create', ['room_id' => $room->id]) }}"
                                        class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1"
                                        title="Pinjam Ruangan Ini">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        <span>Pinjam</span>
                                    </a>
                                @endif

                                <!-- Tombol Edit -->
                                <a href="{{ route('rooms.edit', $room) }}"
                                    class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Ubah Ruangan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>

                                <!-- Tombol Hapus -->
                                <form method="POST" action="{{ route('rooms.destroy', $room) }}" class="inline"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus ruangan \'{{ $room->room_name }}\'?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Ruangan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        @if ($room->is_active)
                            <div class="mt-4 pt-3 border-t border-slate-100">
                                <a href="{{ route('events.create', ['room_id' => $room->id]) }}"
                                    class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span>Ajukan Pinjam Ruangan Ini</span>
                                </a>
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
