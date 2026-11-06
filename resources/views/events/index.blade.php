@extends('layouts.app')

@section('title', 'Agenda & Peminjaman Ruangan')

@section('content')
<div class="space-y-8">

    <!-- Header & Action Button -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 mb-2">
                <span>Manajemen Jadwal Sekolah</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Agenda & Peminjaman Ruangan</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Kelola permohonan penggunaan ruangan dengan sistem proteksi anti-tabrakan waktu mutlak dan pemantauan real-time.
            </p>
        </div>

        @if (Auth::user()->isGuru() || Auth::user()->isAdmin())
            <a href="{{ route('events.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs font-semibold transition shadow-xs shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Ajukan Peminjaman Ruang</span>
            </a>
        @endif
    </div>

    <!-- REVISI GURU 1: TIGA BAGIAN UTAMA HALAMAN AGENDA -->
    <div class="space-y-6">

        <!-- 1. BAGIAN: AGENDA TERKINI (SEDANG BERLANGSUNG) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-3 w-3 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Agenda Terkini</h2>
                        <p class="text-xs text-slate-500">Kegiatan yang sedang berlangsung saat ini di ruangan sekolah</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-lg border border-emerald-200">
                    {{ $agendaTerkini->count() }} Sedang Berjalan
                </span>
            </div>

            @if ($agendaTerkini->isEmpty())
                <div class="p-6 text-center text-xs text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                    <svg class="w-8 h-8 text-slate-300 mx-auto mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Tidak ada agenda yang sedang berlangsung saat ini. Seluruh ruangan berstatus siap pakai / kosong.</span>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($agendaTerkini as $curr)
                        <div class="p-4 rounded-xl border-2 border-emerald-300 bg-emerald-50/30 flex flex-col justify-between hover:shadow-xs transition">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-600 text-white uppercase tracking-wider">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                        <span>Sedang Berlangsung</span>
                                    </span>
                                    <span class="text-[11px] font-mono text-emerald-800 font-bold">
                                        {{ substr($curr->start_time, 0, 5) }} - {{ substr($curr->end_time, 0, 5) }} WIB
                                    </span>
                                </div>
                                <h3 class="font-bold text-slate-900 text-sm leading-snug">{{ $curr->event_name }}</h3>
                                <div class="mt-2 text-xs text-slate-600">
                                    <div class="font-semibold text-slate-800">{{ $curr->room->room_name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $curr->room->location }}</div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2.5 border-t border-emerald-100 flex items-center justify-between text-[11px] text-slate-500">
                                <span>Pemohon: <strong>{{ $curr->user->fullname }}</strong></span>
                                @if (Auth::user()->isGuru() || Auth::user()->isAdmin())
                                    <a href="{{ route('complaints.create', ['room_id' => $curr->room_id, 'event_id' => $curr->id]) }}"
                                        class="text-amber-700 hover:text-amber-800 font-semibold" title="Lapor masalah sarpras terkait agenda ini">
                                        Lapor Fasilitas &rarr;
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- 2. BAGIAN: AGENDA HARI INI -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Agenda Hari Ini</h2>
                        <p class="text-xs text-slate-500">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 text-xs font-bold rounded-lg border border-blue-200">
                    {{ $agendaHariIni->count() }} Agenda Terjadwal
                </span>
            </div>

            @if ($agendaHariIni->isEmpty())
                <div class="p-6 text-center text-xs text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                    <span>Tidak ada jadwal agenda kegiatan untuk hari ini.</span>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($agendaHariIni as $todayEv)
                        @php
                            $execStatus = $todayEv->execution_status;
                            $cardBorder = match($execStatus) {
                                'Sedang Berlangsung' => 'border-emerald-300 bg-emerald-50/20',
                                'Belum Terlaksana' => 'border-blue-200 bg-blue-50/20',
                                'Sudah Terlaksana' => 'border-slate-200 bg-slate-50/50',
                                default => 'border-amber-200 bg-amber-50/20',
                            };
                            $badgeExec = match($execStatus) {
                                'Sedang Berlangsung' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                'Belum Terlaksana' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                                'Sudah Terlaksana' => 'bg-slate-100 text-slate-700 border-slate-300',
                                default => 'bg-amber-100 text-amber-800 border-amber-300',
                            };
                        @endphp
                        <div class="p-4 rounded-xl border {{ $cardBorder }} flex flex-col justify-between hover:shadow-xs transition">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase tracking-wider {{ $badgeExec }}">
                                        {{ $execStatus }}
                                    </span>
                                    <span class="text-[11px] font-mono text-slate-600 font-semibold">
                                        {{ substr($todayEv->start_time, 0, 5) }} - {{ substr($todayEv->end_time, 0, 5) }} WIB
                                    </span>
                                </div>
                                <h3 class="font-bold text-slate-900 text-sm leading-snug">{{ $todayEv->event_name }}</h3>
                                <div class="mt-2 text-xs text-slate-600">
                                    <div class="font-semibold text-slate-800">{{ $todayEv->room->room_name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $todayEv->room->location }}</div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                                <span>Oleh: <strong>{{ $todayEv->user->fullname }}</strong></span>
                                @if ($todayEv->session)
                                    <span class="text-[10px] font-medium text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">
                                        {{ $todayEv->session->session_name }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- 3. BAGIAN: AGENDA YANG AKAN DATANG -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Agenda Yang Akan Datang</h2>
                        <p class="text-xs text-slate-500">Jadwal kegiatan mendatang yang telah terkonfirmasi (diurutkan tanggal terdekat)</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 bg-purple-50 text-purple-700 text-xs font-bold rounded-lg border border-purple-200">
                    {{ $agendaAkanDatang->count() }} Kegiatan Mendatang
                </span>
            </div>

            @if ($agendaAkanDatang->isEmpty())
                <div class="p-6 text-center text-xs text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                    <span>Belum ada agenda kegiatan yang terjadwal untuk hari-hari mendatang.</span>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($agendaAkanDatang as $upEv)
                        <div class="p-4 rounded-xl border border-slate-200 bg-white hover:border-purple-300 hover:shadow-xs transition flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                        {{ $upEv->formatted_date_range }}
                                    </span>
                                    <span class="text-[11px] font-mono text-slate-600">
                                        {{ substr($upEv->start_time, 0, 5) }} - {{ substr($upEv->end_time, 0, 5) }} WIB
                                    </span>
                                </div>
                                <h3 class="font-bold text-slate-900 text-sm leading-snug">{{ $upEv->event_name }}</h3>
                                <div class="mt-2 text-xs text-slate-600">
                                    <div class="font-semibold text-slate-800">{{ $upEv->room->room_name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $upEv->room->location }}</div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                                <span>Pemohon: <strong>{{ $upEv->user->fullname }}</strong></span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                                    Belum Terlaksana
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

    <!-- PENCARIAN & TABEL LENGKAP AGENDA (DIURUTKAN TANGGAL TERDEKAT DARI HARI INI) -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900 tracking-tight">Semua Riwayat & Penelusuran Agenda</h2>
                <p class="text-xs text-slate-500">Daftar agenda lengkap dengan status persetujuan, pelaksanaan, dan hak veto.</p>
            </div>
            <div class="text-xs text-slate-500 font-medium">
                Urutan: <strong class="text-slate-800">Tanggal Terdekat dari Hari Ini</strong>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
            <form method="GET" action="{{ route('events.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-4">
                    <input type="text" name="q" value="{{ request('q') }}"
                        placeholder="Cari kegiatan atau nama pemohon..."
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                </div>
                <div class="sm:col-span-3">
                    <select name="room_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white">
                        <option value="">Semua Ruangan</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                                {{ $room->room_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <select name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white">
                        <option value="">Semua Status</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <input type="date" name="date" value="{{ request('date') }}"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white">
                </div>
                <div class="sm:col-span-1 flex gap-1">
                    <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold transition" title="Terapkan Filter">
                        Cari
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabel Daftar Agenda -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
            @if ($events->isEmpty())
                <div class="py-12 text-center text-xs text-slate-400">
                    Tidak ada agenda yang sesuai dengan kriteria filter Anda.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 uppercase font-semibold border-b border-slate-200">
                            <tr>
                                <th class="p-4">Agenda / Kegiatan</th>
                                <th class="p-4">Ruangan & Lokasi</th>
                                <th class="p-4">Tanggal & Waktu</th>
                                <th class="p-4">Pemohon</th>
                                <th class="p-4">Status & Pelaksanaan</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($events as $event)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 text-sm">{{ $event->event_name }}</div>
                                        @if ($event->move_reason)
                                            <div class="mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-800 border border-amber-200">
                                                <span>Dipindahkan Waka: "{{ $event->move_reason }}"</span>
                                            </div>
                                        @endif
                                        @if ($event->status === 'rejected' && $event->alasan_penolakan)
                                            <div class="mt-1.5 p-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-[11px] leading-relaxed">
                                                <span class="font-bold text-rose-900 block">Alasan Penolakan Waka:</span>
                                                <span>"{{ $event->alasan_penolakan }}"</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="p-4">
                                        <div class="font-semibold text-slate-800">{{ $event->room->room_name }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $event->room->location }}</div>
                                    </td>
                                    <td class="p-4 whitespace-nowrap">
                                        <div class="font-semibold text-slate-800">
                                            {{ $event->formatted_date_range }}
                                            @if ($event->isMultiDay())
                                                <span class="ml-1 px-1.5 py-0.2 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                    Multi-Hari
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-blue-600 font-medium">
                                            {{ substr($event->start_time, 0, 5) }} - {{ substr($event->end_time, 0, 5) }} WIB
                                        </div>
                                        @if ($event->session)
                                            <div class="mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                <span>{{ $event->session->session_name }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="p-4">
                                        <div class="font-medium text-slate-900">{{ $event->user->fullname }}</div>
                                        <div class="text-[11px] text-slate-400 capitalize">{{ $event->user->role->role_name }}</div>
                                    </td>
                                    <td class="p-4 whitespace-nowrap">
                                        <!-- REVISI PIC 1 & 2: Status Pelaksanaan Otomatis & Badge Kontras -->
                                        @if ($event->status === 'pending')
                                            <div>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-bold text-xs bg-amber-100 text-amber-900 border border-amber-300 shadow-xs">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                    <span>PENDING</span>
                                                </span>
                                                <div class="text-[10px] text-amber-700 mt-1 font-medium">Menunggu Verifikasi Waka</div>
                                            </div>
                                        @elseif ($event->status === 'rejected')
                                            <div>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-bold text-xs bg-rose-100 text-rose-800 border border-rose-300 shadow-xs">
                                                    <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    <span>DITOLAK</span>
                                                </span>
                                            </div>
                                        @else
                                            <!-- Status Approved: Tampilkan Status Pelaksanaan Otomatis -->
                                            @php
                                                $execStatus = $event->execution_status;
                                                $execBadgeClass = match($execStatus) {
                                                    'Sedang Berlangsung' => 'bg-emerald-500 text-white font-bold shadow-xs',
                                                    'Belum Terlaksana' => 'bg-indigo-100 text-indigo-800 border border-indigo-300 font-bold',
                                                    'Sudah Terlaksana' => 'bg-slate-100 text-slate-700 border border-slate-300 font-semibold',
                                                    default => 'bg-emerald-100 text-emerald-800 border border-emerald-300',
                                                };
                                            @endphp
                                            <div>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs {{ $execBadgeClass }}">
                                                    @if ($execStatus === 'Sedang Berlangsung')
                                                        <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                                                    @elseif ($execStatus === 'Belum Terlaksana')
                                                        <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    @else
                                                        <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    @endif
                                                    <span>{{ $execStatus }}</span>
                                                </span>
                                                <div class="text-[10px] text-emerald-700 mt-1 font-medium flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                    <span>Disetujui @if ($event->approver) oleh {{ $event->approver->fullname }} @endif</span>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="p-4 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            <!-- Tombol Aksi Cepat Laporkan Masalah Fasilitas -->
                                            @if (Auth::user()->isGuru() || Auth::user()->isAdmin())
                                                <a href="{{ route('complaints.create', ['room_id' => $event->room_id, 'event_id' => $event->id]) }}"
                                                    class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-lg text-xs font-semibold inline-flex items-center gap-1 transition shadow-xs"
                                                    title="Buat pengaduan sarpras terkait ruangan dan agenda ini">
                                                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                    </svg>
                                                    <span>Lapor Masalah</span>
                                                </a>
                                            @endif

                                            <!-- Tombol Persetujuan Waka -->
                                            @if (Auth::user()->isAdmin())
                                                @if ($event->status === 'pending')
                                                    <form method="POST" action="{{ route('events.approve', $event) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition" title="Setujui Agenda">
                                                            Setujui
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                        onclick="bukaModalTolak('{{ $event->id }}', '{{ addslashes($event->event_name) }}', '{{ addslashes($event->user->fullname) }}')"
                                                        class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold shadow-xs transition" title="Tolak Pengajuan dengan Alasan Wajib">
                                                        Tolak
                                                    </button>
                                                @elseif ($event->status === 'approved')
                                                    <a href="{{ route('events.move-form', $event) }}"
                                                        class="px-2.5 py-1 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 rounded-lg text-xs font-semibold transition" title="Hak Veto Waka: Pindahkan Ruangan Terpinjam">
                                                        Pindah Ruang
                                                    </a>
                                                @endif
                                            @endif

                                            <!-- Tombol Batalkan untuk Pemohon (hanya jika pending) atau Admin -->
                                            @if (Auth::user()->isAdmin() || ($event->pic_id === Auth::id() && $event->status === 'pending'))
                                                <form method="POST" action="{{ route('events.destroy', $event) }}" class="inline"
                                                    onsubmit="return confirm('Apakah Anda yakin ingin membatalkan agenda ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Batalkan / Hapus">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($events->hasPages())
                    <div class="p-4 border-t border-slate-100">
                        {{ $events->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

</div>

<!-- MODAL PENOLAKAN PENGAJUAN PINJAMAN (WAJIB ALASAN) -->
<div id="modal-tolak" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 transform transition-all">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900">Tolak Pengajuan Peminjaman</h3>
                <p class="text-xs text-slate-500 mt-0.5">Tuliskan alasan penolakan secara resmi agar dapat dibaca oleh pemohon.</p>
            </div>
        </div>

        <div class="mt-4 p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs">
            <div class="font-bold text-slate-800" id="modal-event-name">-</div>
            <div class="text-slate-500 text-[11px] mt-0.5">Pemohon: <span id="modal-event-user" class="font-medium text-slate-700">-</span></div>
        </div>

        <form id="form-tolak" method="POST" action="" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="alasan_penolakan" class="block text-xs font-semibold text-slate-700 mb-1">
                    Alasan Penolakan <span class="text-rose-500">* (Wajib diisi)</span>
                </label>
                <textarea id="alasan_penolakan" name="alasan_penolakan" rows="3" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 focus:bg-white transition"
                    placeholder="Contoh: Ruangan akan disterilkan untuk persiapan UKK / Jadwal bentrok dengan kegiatan kedinasan sekolah..."></textarea>
                <p class="text-[11px] text-slate-400 mt-1">Alasan ini akan otomatis tampil di riwayat peminjaman pemohon.</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="tutupModalTolak()"
                    class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Batal
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                    Tolak Pengajuan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function bukaModalTolak(eventId, eventName, userName) {
        const form = document.getElementById('form-tolak');
        form.action = `/agenda/${eventId}/tolak`;
        document.getElementById('modal-event-name').textContent = eventName;
        document.getElementById('modal-event-user').textContent = userName;
        document.getElementById('alasan_penolakan').value = '';
        document.getElementById('modal-tolak').classList.remove('hidden');
    }

    function tutupModalTolak() {
        document.getElementById('modal-tolak').classList.add('hidden');
    }

    document.getElementById('modal-tolak').addEventListener('click', function(e) {
        if (e.target === this) {
            tutupModalTolak();
        }
    });
</script>
@endsection
