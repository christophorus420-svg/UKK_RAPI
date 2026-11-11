@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">

    <!-- Welcome Banner Modern & Elegan -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 sm:p-8 text-white shadow-md border border-slate-800 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="relative z-10 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold mb-3
                {{ $userRole === 'admin' ? 'bg-purple-500/20 text-purple-200 border border-purple-400/30' : ($userRole === 'pic' ? 'bg-amber-500/20 text-amber-200 border border-amber-400/30' : ($userRole === 'guru' ? 'bg-emerald-500/20 text-emerald-200 border border-emerald-400/30' : 'bg-slate-500/20 text-slate-200 border border-slate-400/30')) }}">
                <span class="w-2 h-2 rounded-full {{ $userRole === 'admin' ? 'bg-purple-400' : ($userRole === 'pic' ? 'bg-amber-400' : ($userRole === 'guru' ? 'bg-emerald-400' : 'bg-slate-400')) }} animate-pulse"></span>
                <span>Peran:</span>
                <span class="capitalize font-bold">{{ $userRole === 'admin' ? 'Waka Sarpras / Admin' : ($userRole === 'pic' ? 'PIC Ruangan' : ($userRole === 'guru' ? 'Guru / Pembina Organisasi' : 'Siswa / Tamu')) }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white drop-shadow-xs">
                Halo, {{ Auth::user()->fullname }}
            </h1>
            <p class="text-slate-300 text-xs sm:text-sm mt-1.5 leading-relaxed">
                Platform terpadu reservasi ruangan dan pemeliharaan sarana prasarana sekolah dengan validasi cerdas anti-bentrok.
            </p>
        </div>
        <div class="relative z-10 text-left md:text-right bg-white/10 backdrop-blur-md p-3.5 sm:p-4 rounded-xl border border-white/10 w-full md:w-auto shrink-0 shadow-inner">
            <div class="text-xs font-semibold text-slate-200">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</div>
            <div class="text-sm font-bold text-white mt-0.5">Pukul <span id="clock" class="font-mono text-amber-300">{{ \Carbon\Carbon::now()->format('H:i') }}</span> WIB</div>
            <div class="text-[10px] text-slate-300 mt-1 flex items-center gap-1 md:justify-end">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                <span>Sistem Siap Operasional</span>
            </div>
        </div>
    </div>

    <!-- POP-UP MODAL & BANNER DINAMIS KHUSUS GURU: NOTIFIKASI PEMINDAHAN RUANG OLEH WAKA -->
    @if ($userRole === 'guru' && isset($agendaDipindahkan) && $agendaDipindahkan->count() > 0)
        <!-- Modal Pop-Up Dialog Interaktif -->
        <div id="modal-notifikasi-pindah" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border-2 border-amber-400 transform transition-all">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 uppercase mb-1">
                            Pemberitahuan Mendesak Waka
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Ruangan Kegiatan Anda Telah Dipindahkan</h3>
                        <p class="text-xs text-slate-500 mt-1">
                            Waka Sarpras telah menggunakan hak veto pemindahan ruangan untuk agenda berikut:
                        </p>
                    </div>
                </div>

                <div class="mt-4 space-y-3 max-h-60 overflow-y-auto">
                    @foreach ($agendaDipindahkan as $pindah)
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                            <div class="font-bold text-slate-900 text-sm">{{ $pindah->event_name }}</div>
                            <div class="text-slate-600 mt-1 flex items-center justify-between">
                                <span>Tanggal: {{ \Carbon\Carbon::parse($pindah->date)->translatedFormat('d M Y') }} ({{ substr($pindah->start_time, 0, 5) }} - {{ substr($pindah->end_time, 0, 5) }})</span>
                            </div>
                            <div class="mt-2 text-xs font-semibold text-blue-700 bg-blue-50 p-2 rounded-lg border border-blue-200">
                                Ruangan Pengganti Baru: {{ $pindah->room->room_name }} ({{ $pindah->room->location }})
                            </div>
                            <div class="mt-2 text-xs text-slate-700 bg-amber-50 p-2.5 rounded-lg border border-amber-200">
                                <strong class="text-amber-900">Alasan Pemindahan Waka:</strong>
                                <div class="italic text-slate-800 mt-0.5">"{{ $pindah->move_reason }}"</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" onclick="document.getElementById('modal-notifikasi-pindah').classList.add('hidden')"
                        class="w-full sm:w-auto px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                        Saya Mengerti & Tutup Notifikasi
                    </button>
                </div>
            </div>
        </div>

        <!-- Banner Peringatan Tetap di Dashboard -->
        <div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-5 shadow-xs">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-bold text-amber-900">
                        Pemberitahuan Pemindahan Ruangan Kegiatan
                    </h3>
                    <p class="text-xs text-amber-800 mt-0.5">
                        Waka Sarpras telah memindahkan ruangan untuk agenda kegiatan Anda karena alasan mendesak:
                    </p>

                    <div class="mt-3 space-y-2">
                        @foreach ($agendaDipindahkan as $pindah)
                            <div class="bg-white/90 p-3 rounded-xl border border-amber-200 text-xs">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between font-semibold text-slate-800 gap-1">
                                    <span>{{ $pindah->event_name }} ({{ \Carbon\Carbon::parse($pindah->date)->translatedFormat('d M Y') }})</span>
                                    <span class="text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                        Ruang Baru: {{ $pindah->room->room_name }}
                                    </span>
                                </div>
                                <div class="mt-2 text-slate-700 bg-amber-50/50 p-2 rounded border border-amber-100">
                                    <strong class="text-amber-900">Alasan Pemindahan Waka:</strong>
                                    <span class="italic text-slate-800">"{{ $pindah->move_reason }}"</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- 4 Kartu Metrik Statistik Utama -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Ruangan -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase">Total Ruang</span>
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </span>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">{{ $totalRuangan }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ $ruanganAktif }} siap digunakan</div>
        </div>

        <!-- Agenda Hari Ini -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase">Agenda Hari Ini</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </span>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">{{ $agendaHariIni }}</div>
            <div class="text-xs text-slate-500 mt-1">Kegiatan terdaftar</div>
        </div>

        <!-- Komplain Belum Selesai -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase">Kerusakan Sarpras</span>
                <span class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </span>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">{{ $komplainBelumSelesai }}</div>
            <div class="text-xs text-rose-600 font-medium mt-1">Menunggu penanganan</div>
        </div>

        <!-- Shortcut Cepat -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="text-xs font-semibold text-slate-500 uppercase">Aksi Cepat</div>
            <div class="flex flex-col gap-1.5 mt-2">
                @if ($userRole === 'admin')
                    <a href="{{ route('rooms.index') }}" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                        + Tambah / Kelola Ruangan
                    </a>
                    <a href="{{ route('events.index') }}" class="text-xs text-purple-600 hover:text-purple-800 font-medium">
                        &rarr; Persetujuan & Pindah Ruang
                    </a>
                @elseif ($userRole === 'pic')
                    <a href="{{ route('complaints.index') }}" class="text-xs text-amber-600 hover:text-amber-800 font-medium">
                        &rarr; Tangani Fasilitas Rusak
                    </a>
                    <a href="{{ route('calendar.public') }}" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                        &rarr; Lihat Kalender Ruang
                    </a>
                @elseif ($userRole === 'guru')
                    <a href="{{ route('events.create') }}" class="text-xs text-emerald-600 hover:text-emerald-800 font-medium font-semibold">
                        + Ajukan Peminjaman Ruang
                    </a>
                    <a href="{{ route('complaints.create') }}" class="text-xs text-rose-600 hover:text-rose-800 font-medium">
                        + Laporkan Fasilitas Rusak
                    </a>
                @else
                    <a href="{{ route('calendar.public') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold">
                        &rarr; Buka Kalender Publik
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- TAMPILAN KONTEN SPESIFIK BERDASARKAN ROLE -->

    @if ($userRole === 'admin')
        <!-- REVISI ADMIN 3: WIDGET RUANG KOSONG SAAT INI (REAL-TIME AVAILABILITY) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-lg border border-emerald-200 shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-bold text-slate-900">Status "Ruang Kosong" & Ketersediaan Real-Time</h2>
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Menampilkan ruangan yang tidak sedang dipakai atau dipinjam pada hari dan waktu saat ini ({{ \Carbon\Carbon::now()->format('H:i') }} WIB).
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-lg border border-emerald-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>{{ $ruangKosongList->count() }} Ruangan Kosong</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-lg border border-blue-300">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        <span>{{ $ruangTerpakaiList->count() }} Sedang Terpakai</span>
                    </span>
                </div>
            </div>

            <!-- Grid Kartu Ruangan Real-Time -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3.5">
                @foreach ($statusRuangList as $sr)
                    @if ($sr->is_empty)
                        <!-- Kartu Ruangan KOSONG (Tersedia) -->
                        <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/20 hover:bg-emerald-50/40 hover:shadow-xs transition flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-1 mb-2">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 uppercase">
                                        <svg class="w-3 h-3 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>KOSONG SAAT INI</span>
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-medium">Kapasitas: {{ $sr->room->capacity }} org</span>
                                </div>
                                <h4 class="font-bold text-slate-900 text-sm leading-tight">{{ $sr->room->room_name }}</h4>
                                <div class="text-[11px] text-slate-500 mt-0.5">{{ $sr->room->location }}</div>

                                <div class="mt-2.5 p-2 rounded-lg bg-white/80 border border-emerald-100 text-[11px]">
                                    @if ($sr->next_event)
                                        <div class="text-amber-800 font-medium">
                                            <span class="font-bold">Agenda berikutnya hari ini:</span><br>
                                            pukul {{ substr($sr->next_event->start_time, 0, 5) }} - {{ substr($sr->next_event->end_time, 0, 5) }} WIB<br>
                                            <span class="italic text-slate-600 truncate block">"{{ $sr->next_event->event_name }}"</span>
                                        </div>
                                    @else
                                        <div class="text-emerald-700 font-medium flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span>Tidak ada agenda tersisa hari ini.</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 pt-2.5 border-t border-emerald-100 space-y-2">
                                <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span class="truncate" title="{{ $sr->room->pic->fullname }}">PIC: <strong class="text-slate-700">{{ $sr->room->pic->fullname }}</strong></span>
                                </div>
                                <a href="{{ route('events.create', ['room_id' => $sr->room->id]) }}"
                                    class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-xs hover:shadow-sm transition group">
                                    <svg class="w-3.5 h-3.5 text-emerald-100 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span>Pinjam Ruangan Ini</span>
                                </a>
                            </div>
                        </div>
                    @else
                        <!-- Kartu Ruangan SEDANG TERPAKAI -->
                        <div class="p-4 rounded-xl border border-blue-200 bg-blue-50/20 hover:shadow-xs transition flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-1 mb-2">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-600 text-white uppercase tracking-wider">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                        <span>SEDANG DIGUNAKAN</span>
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-medium">{{ substr($sr->active_event->start_time, 0, 5) }} - {{ substr($sr->active_event->end_time, 0, 5) }}</span>
                                </div>
                                <h4 class="font-bold text-slate-900 text-sm leading-tight">{{ $sr->room->room_name }}</h4>
                                <div class="text-[11px] text-slate-500 mt-0.5">{{ $sr->room->location }}</div>

                                <div class="mt-2.5 p-2 rounded-lg bg-white/80 border border-blue-100 text-[11px] space-y-0.5">
                                    <div class="font-bold text-blue-900 truncate">"{{ $sr->active_event->event_name }}"</div>
                                    <div class="text-slate-500">Pemohon: <span class="font-medium text-slate-700">{{ $sr->active_event->user->fullname }}</span></div>
                                </div>
                            </div>

                            <div class="mt-3 pt-2.5 border-t border-blue-100 space-y-2">
                                <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span class="truncate" title="{{ $sr->room->pic->fullname }}">PIC: <strong class="text-slate-700">{{ $sr->room->pic->fullname }}</strong></span>
                                </div>
                                <a href="{{ route('events.index', ['room_id' => $sr->room->id]) }}"
                                    class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-xs hover:shadow-sm transition group">
                                    <span>Lihat Detail Agenda</span>
                                    <svg class="w-3.5 h-3.5 text-blue-100 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- DASHBOARD WAKA/ADMIN: DAFTAR PERSETUJUAN & ESKALASI WHATSAPP -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Card Peringatan Eskalasi WhatsApp Kerusakan Terbengkalai -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Eskalasi Kerusakan ke PIC</h2>
                        <p class="text-xs text-slate-500">Fasilitas 'Belum Diperbaiki' melewati batas waktu</p>
                    </div>
                    <span class="px-2 py-1 bg-rose-100 text-rose-700 text-xs font-semibold rounded-lg">
                        {{ $daftarKomplainPerluEskalasi->count() }} Perlu Perhatian
                    </span>
                </div>

                @if ($daftarKomplainPerluEskalasi->isEmpty())
                    <div class="py-8 text-center text-xs text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        Tidak ada pengaduan terbengkalai saat ini. Semua fasilitas ditangani dengan baik.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($daftarKomplainPerluEskalasi as $komplain)
                            @php
                                $badgeStatusClass = match($komplain->status_repair) {
                                    'Sudah Diperbaiki' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    'Menunggu Spare Part' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    default => 'bg-rose-100 text-rose-700 border-rose-200',
                                };
                            @endphp
                            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <div class="flex items-start gap-3 flex-1">
                                    @if ($komplain->photo_url)
                                        <button type="button"
                                            onclick="openPhotoModal({
                                                url: '{{ $komplain->photo_url }}',
                                                room: '{{ addslashes($komplain->room->room_name) }}',
                                                location: '{{ addslashes($komplain->room->location) }}',
                                                reporter: '{{ addslashes($komplain->reporter->fullname) }}',
                                                pic: '{{ addslashes($komplain->handler?->fullname ?? $komplain->room->pic->fullname) }}',
                                                status: '{{ addslashes($komplain->status_repair) }}',
                                                statusClass: '{{ $badgeStatusClass }}',
                                                time: '{{ \Carbon\Carbon::parse($komplain->reported_at)->translatedFormat('d F Y, H:i') }} WIB',
                                                description: '{{ addslashes($komplain->description) }}'
                                            })"
                                            class="shrink-0 w-12 h-12 rounded-lg overflow-hidden border border-slate-300 relative group cursor-pointer hover:ring-2 hover:ring-blue-400 transition"
                                            title="Klik untuk melihat bukti foto">
                                            <img src="{{ $komplain->photo_url }}" alt="Foto" class="w-full h-full object-cover group-hover:scale-110 transition duration-200"
                                                onerror="this.onerror=null; this.src='{{ asset('images/placeholder-no-image.svg') }}';">
                                            <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                            </div>
                                        </button>
                                    @else
                                        <div class="shrink-0 w-12 h-12 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400" title="Tanpa Lampiran Foto">
                                            <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $komplain->room->room_name }}</div>
                                        <div class="text-slate-600 line-clamp-1 mt-0.5">{{ $komplain->description }}</div>
                                        <div class="text-[11px] text-slate-400 mt-1 flex flex-wrap items-center gap-1.5">
                                            <span>PIC: <strong class="text-slate-700">{{ $komplain->handler?->fullname ?? $komplain->room->pic->fullname }}</strong></span>
                                            <span>• Dilaporkan {{ \Carbon\Carbon::parse($komplain->reported_at)->diffForHumans() }}</span>
                                            @if ($komplain->photo_url)
                                                <button type="button"
                                                    onclick="openPhotoModal({
                                                        url: '{{ $komplain->photo_url }}',
                                                        room: '{{ addslashes($komplain->room->room_name) }}',
                                                        location: '{{ addslashes($komplain->room->location) }}',
                                                        reporter: '{{ addslashes($komplain->reporter->fullname) }}',
                                                        pic: '{{ addslashes($komplain->handler?->fullname ?? $komplain->room->pic->fullname) }}',
                                                        status: '{{ addslashes($komplain->status_repair) }}',
                                                        statusClass: '{{ $badgeStatusClass }}',
                                                        time: '{{ \Carbon\Carbon::parse($komplain->reported_at)->translatedFormat('d F Y, H:i') }} WIB',
                                                        description: '{{ addslashes($komplain->description) }}'
                                                    })"
                                                    class="inline-flex items-center gap-1 font-semibold text-blue-600 hover:text-blue-800">
                                                    <span>(Lihat Foto)</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ $komplain->whatsapp_escalation_url }}" target="_blank"
                                    class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg font-semibold text-xs shadow-xs transition" title="Kirim Peringatan WhatsApp API Resmi ke PIC">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-5.705 1.554zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                                    </svg>
                                    <span>Peringatkan PIC via WhatsApp</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Card Pengajuan Agenda Menunggu Persetujuan -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Pengajuan Ruang Baru</h2>
                        <p class="text-xs text-slate-500">Permohonan agenda menunggu persetujuan / verifikasi</p>
                    </div>
                    <a href="{{ route('events.index') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold">
                        Lihat Semua
                    </a>
                </div>

                @if ($daftarPengajuanPending->isEmpty())
                    <div class="py-8 text-center text-xs text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        Tidak ada pengajuan agenda yang berstatus pending.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($daftarPengajuanPending as $pendingEvent)
                            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-slate-900">{{ $pendingEvent->event_name }}</div>
                                    <div class="text-slate-600 mt-0.5">
                                        {{ $pendingEvent->room->room_name }} • {{ \Carbon\Carbon::parse($pendingEvent->date)->translatedFormat('d M Y') }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-1">
                                        Pemohon: <span class="font-medium text-slate-700">{{ $pendingEvent->user->fullname }}</span> ({{ substr($pendingEvent->start_time, 0, 5) }} - {{ substr($pendingEvent->end_time, 0, 5) }})
                                    </div>
                                </div>
                                <a href="{{ route('events.index') }}" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-xs transition">
                                    Tinjau
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

        <!-- KARTU PENGATURAN SESI WAKTU PEMINJAMAN RUANGAN (WAKA / ADMIN) -->
        <div class="mt-6 bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Pengaturan Waktu Sesi Peminjaman</h2>
                        <p class="text-xs text-slate-500">Kelola rentang jam operasional sesi (Sesi 1 s.d. 4) yang digunakan pada form peminjaman & validasi bentrok jadwal</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 text-blue-700 text-xs font-semibold rounded-lg border border-blue-200">
                        <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                        {{ $daftarSesi->where('is_active', true)->count() }} dari {{ $daftarSesi->count() }} Sesi Aktif
                    </span>
                </div>
            </div>

            <!-- Grid Sesi Waktu (13 Sesi @ 45 Menit) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5">
                @foreach ($daftarSesi as $sesi)
                    @php
                        $startFmt = substr($sesi->start_time, 0, 5);
                        $endFmt = substr($sesi->end_time, 0, 5);
                    @endphp
                    <div class="p-3.5 rounded-xl border {{ $sesi->is_active ? 'border-slate-200 bg-slate-50/70 hover:border-blue-300' : 'border-slate-200 bg-slate-100/60 opacity-75' }} transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-1.5">
                                <span class="font-bold text-xs text-slate-900 truncate" title="{{ $sesi->session_name }}">{{ $sesi->session_name }}</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider shrink-0 {{ $sesi->is_active ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $sesi->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>

                            <div class="mt-2.5 flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-extrabold text-slate-800">
                                        {{ $startFmt }} - {{ $endFmt }}
                                        <span class="text-[10px] font-semibold text-slate-400">WIB</span>
                                    </div>
                                    <div class="text-[11px] text-blue-600 font-medium">
                                        Durasi: 45 Menit
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 pt-2.5 border-t border-slate-200/80 flex items-center gap-2">
                            <button type="button"
                                onclick="openEditSessionModal({
                                    id: {{ $sesi->id }},
                                    name: '{{ addslashes($sesi->session_name) }}',
                                    start: '{{ $startFmt }}',
                                    end: '{{ $endFmt }}',
                                    active: {{ $sesi->is_active ? 'true' : 'false' }},
                                    actionUrl: '{{ route('sessions.update', $sesi->id) }}'
                                })"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span>Edit Waktu</span>
                            </button>

                            <form action="{{ route('sessions.toggle', $sesi->id) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    title="{{ $sesi->is_active ? 'Nonaktifkan Sesi' : 'Aktifkan Sesi' }}"
                                    class="p-1.5 rounded-lg border text-xs font-semibold transition cursor-pointer {{ $sesi->is_active ? 'border-slate-300 text-slate-600 hover:bg-slate-200' : 'border-emerald-300 text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                                    @if ($sesi->is_active)
                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @endif
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    @elseif ($userRole === 'pic')
        <!-- DASHBOARD PIC: RUANGAN TANGGUNG JAWAB & DAFTAR KOMPLAIN MASUK -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Ruangan yang Didelegasikan ke PIC ini (One-to-Many) -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Ruangan Tanggung Jawab Saya</h2>
                        <p class="text-xs text-slate-500">Ruangan yang didelegasikan Waka kepada Anda</p>
                    </div>
                    <span class="px-2 py-1 bg-amber-100 text-amber-700 text-xs font-semibold rounded-lg">
                        {{ $ruanganDikelola->count() }} Ruangan
                    </span>
                </div>

                <div class="space-y-2">
                    @foreach ($ruanganDikelola as $r)
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between text-xs">
                            <div>
                                <div class="font-bold text-slate-900">{{ $r->room_name }}</div>
                                <div class="text-slate-500">{{ $r->location }} • Kapasitas: {{ $r->capacity }} orang</div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $r->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $r->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Komplain pada Ruangan PIC -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Aduan Kerusakan Masuk</h2>
                        <p class="text-xs text-slate-500">Fasilitas yang memerlukan perbaikan dari Anda</p>
                    </div>
                    <a href="{{ route('complaints.index') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold">
                        Kelola Status
                    </a>
                </div>

                @if ($daftarKomplainPic->isEmpty())
                    <div class="py-8 text-center text-xs text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        Tidak ada laporan kerusakan pada ruangan Anda.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($daftarKomplainPic as $c)
                            @php
                                $badgeClass = match($c->status_repair) {
                                    'Sudah Diperbaiki' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    'Menunggu Spare Part' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    default => 'bg-rose-100 text-rose-700 border-rose-200',
                                };
                            @endphp
                            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-start gap-3 flex-1">
                                    @if ($c->photo_url)
                                        <button type="button"
                                            onclick="openPhotoModal({
                                                url: '{{ $c->photo_url }}',
                                                room: '{{ addslashes($c->room->room_name) }}',
                                                location: '{{ addslashes($c->room->location) }}',
                                                reporter: '{{ addslashes($c->reporter->fullname) }}',
                                                pic: '{{ addslashes($c->handler?->fullname ?? $c->room->pic->fullname) }}',
                                                status: '{{ addslashes($c->status_repair) }}',
                                                statusClass: '{{ $badgeClass }}',
                                                time: '{{ \Carbon\Carbon::parse($c->reported_at)->translatedFormat('d F Y, H:i') }} WIB',
                                                description: '{{ addslashes($c->description) }}'
                                            })"
                                            class="shrink-0 w-12 h-12 rounded-lg overflow-hidden border border-slate-300 relative group cursor-pointer hover:ring-2 hover:ring-amber-400 transition"
                                            title="Klik untuk melihat bukti foto">
                                            <img src="{{ $c->photo_url }}" alt="Foto" class="w-full h-full object-cover group-hover:scale-110 transition duration-200"
                                                onerror="this.onerror=null; this.src='{{ asset('images/placeholder-no-image.svg') }}';">
                                            <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                            </div>
                                        </button>
                                    @else
                                        <div class="shrink-0 w-12 h-12 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400" title="Tanpa Lampiran Foto">
                                            <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $c->room->room_name }}</div>
                                        <div class="text-slate-600 mt-0.5">{{ $c->description }}</div>
                                        <div class="text-[11px] text-slate-400 mt-1 flex flex-wrap items-center gap-1.5">
                                            <span>Pelapor: {{ $c->reporter->fullname }} • {{ \Carbon\Carbon::parse($c->reported_at)->diffForHumans() }}</span>
                                            @if ($c->photo_url)
                                                <button type="button"
                                                    onclick="openPhotoModal({
                                                        url: '{{ $c->photo_url }}',
                                                        room: '{{ addslashes($c->room->room_name) }}',
                                                        location: '{{ addslashes($c->room->location) }}',
                                                        reporter: '{{ addslashes($c->reporter->fullname) }}',
                                                        pic: '{{ addslashes($c->handler?->fullname ?? $c->room->pic->fullname) }}',
                                                        status: '{{ addslashes($c->status_repair) }}',
                                                        statusClass: '{{ $badgeClass }}',
                                                        time: '{{ \Carbon\Carbon::parse($c->reported_at)->translatedFormat('d F Y, H:i') }} WIB',
                                                        description: '{{ addslashes($c->description) }}'
                                                    })"
                                                    class="inline-flex items-center gap-1 font-semibold text-blue-600 hover:text-blue-800">
                                                    <span>(Lihat Bukti Foto)</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <span class="px-2 py-1 rounded-lg text-xs font-semibold self-start sm:self-auto shrink-0
                                    {{ $c->status_repair === 'Sudah Diperbaiki' ? 'bg-emerald-100 text-emerald-700' : ($c->status_repair === 'Menunggu Spare Part' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">
                                    {{ $c->status_repair }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- REVISI PIC 3: Jadwal Penggunaan Ruangan Dikelola (Diurutkan Tanggal Terdekat dari Hari Ini) -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Jadwal Penggunaan Ruangan Anda</h2>
                        <p class="text-xs text-slate-500">Agenda terkonfirmasi pada ruangan tanggung jawab Anda (diurutkan tanggal terdekat)</p>
                    </div>
                    <a href="{{ route('events.index') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold">
                        Lihat Seluruh Agenda &rarr;
                    </a>
                </div>

                @if (!isset($jadwalRuanganPic) || $jadwalRuanganPic->isEmpty())
                    <div class="py-8 text-center text-xs text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        Belum ada jadwal peminjaman pada ruangan yang Anda kelola.
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach ($jadwalRuanganPic as $jp)
                            @php
                                $execStatus = $jp->execution_status;
                                $execBadge = match($execStatus) {
                                    'Sedang Berlangsung' => 'bg-emerald-500 text-white font-bold',
                                    'Belum Terlaksana' => 'bg-indigo-100 text-indigo-800 border-indigo-200 font-bold',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between gap-1 mb-1.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] uppercase tracking-wider {{ $execBadge }}">
                                            {{ $execStatus }}
                                        </span>
                                        <span class="font-mono text-slate-500 text-[11px]">{{ substr($jp->start_time, 0, 5) }} - {{ substr($jp->end_time, 0, 5) }}</span>
                                    </div>
                                    <div class="font-bold text-slate-900">{{ $jp->event_name }}</div>
                                    <div class="text-slate-600 mt-1 font-semibold">{{ $jp->room->room_name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $jp->formatted_date_range }}</div>
                                </div>
                                <div class="mt-2.5 pt-2 border-t border-slate-200 flex items-center justify-between text-[11px] text-slate-500">
                                    <span>Pemohon: <strong class="text-slate-700">{{ $jp->user->fullname }}</strong></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

    @elseif ($userRole === 'guru')
        <!-- DASHBOARD GURU: AGENDA SAYA & RIWAYAT PENGADUAN -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Agenda Saya -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Agenda Peminjaman Saya</h2>
                        <p class="text-xs text-slate-500">Daftar agenda yang telah Anda ajukan</p>
                    </div>
                    <a href="{{ route('events.create') }}" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-xs transition">
                        + Ajukan Baru
                    </a>
                </div>

                @if ($agendaSaya->isEmpty())
                    <div class="py-8 text-center text-xs text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        Belum ada agenda peminjaman yang Anda ajukan.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($agendaSaya as $ev)
                            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $ev->event_name }}</div>
                                        <div class="text-slate-600 mt-0.5">
                                            {{ $ev->room->room_name }} • {{ \Carbon\Carbon::parse($ev->date)->translatedFormat('d M Y') }}
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-1">
                                            Pukul {{ substr($ev->start_time, 0, 5) }} - {{ substr($ev->end_time, 0, 5) }} WIB
                                            @if ($ev->session)
                                                <span class="text-indigo-600 font-bold ml-1">• {{ $ev->session->session_name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if ($ev->status === 'pending')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold shrink-0 bg-amber-100 text-amber-900 border border-amber-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                            <span>Pending</span>
                                        </span>
                                    @elseif ($ev->status === 'rejected')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold shrink-0 bg-rose-100 text-rose-800 border border-rose-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                            <span>Ditolak</span>
                                        </span>
                                    @else
                                        @php
                                            $execSt = $ev->execution_status;
                                            $bClass = match($execSt) {
                                                'Sedang Berlangsung' => 'bg-emerald-500 text-white font-bold',
                                                'Belum Terlaksana' => 'bg-indigo-100 text-indigo-800 border-indigo-200 font-bold',
                                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                                            };
                                        @endphp
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-semibold shrink-0 {{ $bClass }}">
                                            {{ $execSt }}
                                        </span>
                                    @endif
                                </div>

                                @if ($ev->status === 'rejected' && $ev->alasan_penolakan)
                                    <div class="mt-2 text-xs bg-rose-50 text-rose-800 p-2 rounded-lg border border-rose-200">
                                        <strong class="text-rose-900 block font-semibold">Alasan Penolakan Waka:</strong>
                                        <span class="italic">"{{ $ev->alasan_penolakan }}"</span>
                                    </div>
                                @endif

                                <div class="mt-2.5 pt-2 border-t border-slate-200/60 flex items-center justify-between">
                                    <span class="text-[10px] text-slate-400">ID #{{ $ev->id }}</span>
                                    <a href="{{ route('complaints.create', ['room_id' => $ev->room_id, 'event_id' => $ev->id]) }}"
                                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 px-2 py-0.5 rounded-lg border border-rose-200 transition"
                                        title="Laporkan masalah sarpras terkait peminjaman ini">
                                        <svg class="w-3 h-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                        <span>Lapor Kendala</span>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Pengaduan Saya -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Laporan Kerusakan Saya</h2>
                        <p class="text-xs text-slate-500">Status perkembangan perbaikan oleh PIC</p>
                    </div>
                    <a href="{{ route('complaints.create') }}" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-semibold text-xs transition">
                        + Lapor Kerusakan
                    </a>
                </div>

                @if ($pengaduanSaya->isEmpty())
                    <div class="py-8 text-center text-xs text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        Anda belum pernah melaporkan kerusakan sarana prasarana.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($pengaduanSaya as $aduan)
                            @php
                                $badgeClass = match($aduan->status_repair) {
                                    'Sudah Diperbaiki' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    'Menunggu Spare Part' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    default => 'bg-rose-100 text-rose-700 border-rose-200',
                                };
                            @endphp
                            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs flex items-center justify-between gap-3">
                                <div class="flex items-start gap-3 flex-1">
                                    @if ($aduan->photo_url)
                                        <button type="button"
                                            onclick="openPhotoModal({
                                                url: '{{ $aduan->photo_url }}',
                                                room: '{{ addslashes($aduan->room->room_name) }}',
                                                location: '{{ addslashes($aduan->room->location) }}',
                                                reporter: '{{ addslashes($aduan->reporter->fullname) }}',
                                                pic: '{{ addslashes($aduan->handler?->fullname ?? $aduan->room->pic->fullname) }}',
                                                status: '{{ addslashes($aduan->status_repair) }}',
                                                statusClass: '{{ $badgeClass }}',
                                                time: '{{ \Carbon\Carbon::parse($aduan->reported_at)->translatedFormat('d F Y, H:i') }} WIB',
                                                description: '{{ addslashes($aduan->description) }}'
                                            })"
                                            class="shrink-0 w-12 h-12 rounded-lg overflow-hidden border border-slate-300 relative group cursor-pointer hover:ring-2 hover:ring-rose-400 transition"
                                            title="Klik untuk melihat bukti foto">
                                            <img src="{{ $aduan->photo_url }}" alt="Foto" class="w-full h-full object-cover group-hover:scale-110 transition duration-200"
                                                onerror="this.onerror=null; this.src='{{ asset('images/placeholder-no-image.svg') }}';">
                                            <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                            </div>
                                        </button>
                                    @else
                                        <div class="shrink-0 w-12 h-12 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400" title="Tanpa Lampiran Foto">
                                            <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $aduan->room->room_name }}</div>
                                        <div class="text-slate-600 mt-0.5 line-clamp-1">{{ $aduan->description }}</div>
                                        <div class="text-[11px] text-slate-400 mt-1 flex flex-wrap items-center gap-1.5">
                                            <span>{{ \Carbon\Carbon::parse($aduan->reported_at)->diffForHumans() }}</span>
                                            @if ($aduan->photo_url)
                                                <button type="button"
                                                    onclick="openPhotoModal({
                                                        url: '{{ $aduan->photo_url }}',
                                                        room: '{{ addslashes($aduan->room->room_name) }}',
                                                        location: '{{ addslashes($aduan->room->location) }}',
                                                        reporter: '{{ addslashes($aduan->reporter->fullname) }}',
                                                        pic: '{{ addslashes($aduan->handler?->fullname ?? $aduan->room->pic->fullname) }}',
                                                        status: '{{ addslashes($aduan->status_repair) }}',
                                                        statusClass: '{{ $badgeClass }}',
                                                        time: '{{ \Carbon\Carbon::parse($aduan->reported_at)->translatedFormat('d F Y, H:i') }} WIB',
                                                        description: '{{ addslashes($aduan->description) }}'
                                                    })"
                                                    class="inline-flex items-center gap-1 font-semibold text-blue-600 hover:text-blue-800">
                                                    <span>(Lihat Bukti Foto)</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <span class="px-2 py-1 rounded-lg text-xs font-semibold shrink-0
                                    {{ $aduan->status_repair === 'Sudah Diperbaiki' ? 'bg-emerald-100 text-emerald-700' : ($aduan->status_repair === 'Menunggu Spare Part' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">
                                    {{ $aduan->status_repair }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

    @else
        <!-- DASHBOARD SISWA / TAMU: AGENDA SEKOLAH -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Agenda Kegiatan Sekolah Terdekat</h2>
                    <p class="text-xs text-slate-500">Informasi kegiatan dan penggunaan ruang sekolah</p>
                </div>
                <a href="{{ route('calendar.public') }}" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-xs transition">
                    Lihat Kalender Interaktif &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($agendaPublik as $ag)
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                        <span class="inline-block px-2 py-0.5 bg-blue-100 text-blue-700 rounded font-semibold text-[11px] mb-2">
                            {{ $ag->room->room_name }}
                        </span>
                        <div class="font-bold text-slate-900 text-sm">{{ $ag->event_name }}</div>
                        <div class="text-slate-500 mt-1">
                            Tanggal: {{ \Carbon\Carbon::parse($ag->date)->translatedFormat('d M Y') }}
                        </div>
                        <div class="text-slate-500 mt-0.5">
                            Pukul {{ substr($ag->start_time, 0, 5) }} - {{ substr($ag->end_time, 0, 5) }} WIB
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>

@include('complaints.photo-modal')
@if ($userRole === 'admin')
    @include('dashboard.session-modal')
@endif
@endsection
