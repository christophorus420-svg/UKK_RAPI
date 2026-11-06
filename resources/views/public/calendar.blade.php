@extends('layouts.app')

@section('title', 'Kalender Agenda & Reservasi Ruangan')

@section('content')
<div class="space-y-6">

    <!-- Banner Header & Statistik Kalender -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 mb-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Transparansi Fasilitas &amp; Agenda Sekolah (Read-Only)</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Kalender Agenda &amp; Penggunaan Ruang
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl leading-relaxed">
                    Sistem transparansi jadwal terintegrasi SMK. Seluruh kegiatan yang tampil telah melewati validasi anti-bentrok dan disetujui resmi oleh Waka/Admin.
                </p>
            </div>

            <!-- Kartu Statistik Cepat -->
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-3">
                <div class="flex-1 sm:flex-none min-w-[130px] p-3 rounded-xl bg-slate-50 border border-slate-200 text-center">
                    <span class="text-xs font-medium text-slate-500 block">Agenda Disetujui</span>
                    <span class="text-xl font-bold text-slate-900">{{ $stats['total_approved_events'] }}</span>
                    <span class="text-[10px] text-slate-400 block">kegiatan aktif</span>
                </div>
                <div class="flex-1 sm:flex-none min-w-[130px] p-3 rounded-xl bg-slate-50 border border-slate-200 text-center">
                    <span class="text-xs font-medium text-slate-500 block">Ruang Dipakai</span>
                    <span class="text-xl font-bold text-blue-600">{{ $stats['total_rooms_used'] }}</span>
                    <span class="text-[10px] text-slate-400 block">dari {{ $stats['active_room_count'] }} ruang</span>
                </div>
                <button type="button" onclick="window.print()" class="hidden md:inline-flex items-center gap-1.5 px-3.5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Cetak</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Toolbar Navigasi Bulan, Filter Ruang, Pencarian, & Toggle Mode -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs space-y-4">
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            
            <!-- Navigasi Bulan Cepat -->
            <div class="flex items-center gap-2">
                <a href="{{ route('calendar.public', array_merge(request()->except(['month', 'year']), ['month' => $prevMonthDate->month, 'year' => $prevMonthDate->year])) }}"
                   class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-sm font-bold transition"
                   title="Bulan Sebelumnya ({{ $prevMonthDate->translatedFormat('F Y') }})">
                    &larr;
                </a>

                <div class="text-center px-3 py-1.5 bg-slate-50 rounded-xl border border-slate-200">
                    <h2 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">
                        {{ $currentMonthDate->translatedFormat('F Y') }}
                    </h2>
                </div>

                <a href="{{ route('calendar.public', array_merge(request()->except(['month', 'year']), ['month' => $nextMonthDate->month, 'year' => $nextMonthDate->year])) }}"
                   class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-sm font-bold transition"
                   title="Bulan Berikutnya ({{ $nextMonthDate->translatedFormat('F Y') }})">
                    &rarr;
                </a>

                <!-- Tombol Shortcut Bulan Ini -->
                <a href="{{ route('calendar.public') }}" 
                   class="ml-2 px-3 py-2 rounded-xl text-xs font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 transition">
                    Hari Ini
                </a>
            </div>

            <!-- Toggle Tampilan: Kalender Grid vs Daftar List -->
            <div class="flex items-center gap-2 self-start md:self-auto">
                <div class="inline-flex p-1 rounded-xl bg-slate-100 border border-slate-200">
                    <a href="{{ route('calendar.public', array_merge(request()->all(), ['view' => 'grid'])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $viewMode === 'grid' ? 'bg-white text-blue-600 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        Grid Kalender
                    </a>
                    <a href="{{ route('calendar.public', array_merge(request()->all(), ['view' => 'list'])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $viewMode === 'list' ? 'bg-white text-blue-600 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        Mode Daftar
                    </a>
                </div>
            </div>
        </div>

        <!-- Form Filter Ruangan, Pencarian & Lompat Bulan -->
        <form method="GET" action="{{ route('calendar.public') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 pt-3 border-t border-slate-100">
            <input type="hidden" name="view" value="{{ $viewMode }}">

            <!-- Dropdown Pilihan Bulan -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Bulan</label>
                <select name="month" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:bg-white">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $selectedMonth === $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>

            <!-- Dropdown Pilihan Tahun -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Tahun</label>
                <select name="year" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:bg-white">
                    @for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                        <option value="{{ $y }}" {{ $selectedYear === $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </div>

            <!-- Filter Ruangan -->
            <div class="lg:col-span-3">
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Filter Ruangan</label>
                <select name="room_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:bg-white">
                    <option value="">Semua Ruangan (Semua Fasilitas)</option>
                    @foreach ($rooms as $r)
                        <option value="{{ $r->id }}" {{ (string)$selectedRoomId === (string)$r->id ? 'selected' : '' }}>
                            {{ $r->room_name }} (Kapasitas: {{ $r->capacity }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Pencarian Kata Kunci -->
            <div class="lg:col-span-3">
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Cari Agenda / Pemohon</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Misal: Ujian Praktik, OSIS, Aula..."
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:bg-white">
            </div>

            <!-- Tombol Aksi -->
            <div class="lg:col-span-2 flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                    Terapkan
                </button>
                @if (!empty($selectedRoomId) || !empty($search) || $selectedMonth !== (int)date('m') || $selectedYear !== (int)date('Y'))
                    <a href="{{ route('calendar.public') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition" title="Reset Filter">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        <!-- Informasi Filter Aktif -->
        @if (!empty($selectedRoomId) || !empty($search))
            <div class="flex items-center gap-2 pt-2 text-xs text-slate-600">
                <span class="font-semibold text-slate-700">Filter Aktif:</span>
                @if (!empty($selectedRoomId))
                    @php $activeRoom = $rooms->firstWhere('id', $selectedRoomId); @endphp
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                        Ruang: {{ $activeRoom ? $activeRoom->room_name : $selectedRoomId }}
                    </span>
                @endif
                @if (!empty($search))
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                        Kata Kunci: "{{ $search }}"
                    </span>
                @endif
            </div>
        @endif
    </div>

    <!-- REVISI GURU 2: LEGENDA & KETERANGAN WARNA TANGGAL KALENDER -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="font-bold text-slate-800 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4 5 5 0 015-5h4a2 2 0 002-2V6a4 4 0 014-4 4 4 0 014 4v2a2 2 0 01-2 2h-2a2 2 0 00-2 2v2a4 4 0 01-4 4H7z"></path>
                </svg>
            </span>
            <span>Legenda Warna Kalender & Hari Penting:</span>
        </div>
        <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-xs">
            <div class="flex items-center gap-1.5">
                <span class="w-3.5 h-3.5 rounded-full bg-blue-600 ring-2 ring-blue-300 shrink-0"></span>
                <span class="text-slate-700 font-medium">Hari Ini</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3.5 h-3.5 rounded bg-indigo-100 border border-indigo-400 shrink-0"></span>
                <span class="text-slate-700 font-medium">Ada Agenda (1 Event)</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3.5 h-3.5 rounded bg-amber-100 border border-amber-400 shrink-0"></span>
                <span class="text-slate-700 font-medium">Agenda Padat (&ge; 2 Event)</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3.5 h-3.5 rounded bg-rose-100 border border-rose-400 shrink-0"></span>
                <span class="text-slate-700 font-medium">Hari Penting / Libur</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3.5 h-3.5 rounded bg-white border border-slate-300 shrink-0"></span>
                <span class="text-slate-700 font-medium">Ruang Kosong</span>
            </div>
        </div>
    </div>

    <!-- TAMPILAN 1: GRID KALENDER BULANAN -->
    @if ($viewMode === 'grid')
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            
            <!-- Header Hari dalam Sepekan (Senin s/d Minggu) -->
            <div class="grid grid-cols-7 bg-slate-100 border-b border-slate-200 text-center font-bold text-xs text-slate-700 uppercase tracking-wider py-2.5">
                <div><span class="hidden sm:inline">Senin</span><span class="sm:hidden">Sen</span></div>
                <div><span class="hidden sm:inline">Selasa</span><span class="sm:hidden">Sel</span></div>
                <div><span class="hidden sm:inline">Rabu</span><span class="sm:hidden">Rab</span></div>
                <div><span class="hidden sm:inline">Kamis</span><span class="sm:hidden">Kam</span></div>
                <div><span class="hidden sm:inline">Jumat</span><span class="sm:hidden">Jum</span></div>
                <div><span class="hidden sm:inline">Sabtu</span><span class="sm:hidden">Sab</span></div>
                <div class="text-rose-600"><span class="hidden sm:inline">Minggu</span><span class="sm:hidden">Min</span></div>
            </div>

            <!-- Matriks Kotak Hari Kalender (7 Kolom) -->
            <div class="grid grid-cols-7 auto-rows-fr divide-x divide-y divide-slate-200 bg-slate-200">

                <!-- Kotak Kosong Sebelum Tanggal 1 Bulan Ini -->
                @for ($b = 0; $b < $leadingBlankDays; $b++)
                    <div class="bg-slate-50/70 min-h-[90px] sm:min-h-[120px] p-2 select-none opacity-40">
                        <!-- Kotak kosong -->
                    </div>
                @endfor

                <!-- Hari Tanggal 1 s/d Hari Terakhir Bulan Ini -->
                @for ($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $dateFormatted = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $day);
                        $monthDayKey = sprintf('%02d-%02d', $selectedMonth, $day);
                        $dayEvents = $eventsByDate->get($dateFormatted, collect());
                        $isToday = ($dateFormatted === $todayString);
                        $carbonDay = \Carbon\Carbon::createFromDate($selectedYear, $selectedMonth, $day);
                        $isSunday = ($carbonDay->dayOfWeekIso === 7);
                        $holidayName = $importantDates[$monthDayKey] ?? null;

                        // Tentukan skema warna khusus sesuai legenda
                        if ($isToday) {
                            $cellClass = 'bg-blue-50/50 ring-2 ring-inset ring-blue-500';
                        } elseif ($dayEvents->count() >= 2) {
                            $cellClass = 'bg-amber-50/60 hover:bg-amber-100/50';
                        } elseif ($dayEvents->count() === 1) {
                            $cellClass = 'bg-indigo-50/40 hover:bg-indigo-100/40';
                        } elseif ($holidayName || $isSunday) {
                            $cellClass = 'bg-rose-50/40 hover:bg-rose-100/40';
                        } else {
                            $cellClass = 'bg-white hover:bg-slate-50';
                        }
                    @endphp

                    <div class="{{ $cellClass }} min-h-[90px] sm:min-h-[125px] p-1.5 sm:p-2.5 flex flex-col justify-between transition">
                        
                        <!-- Header Tanggal & Penanda Hari Penting -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-extrabold {{ $isToday ? 'bg-blue-600 text-white shadow-xs' : ($isSunday || $holidayName ? 'text-rose-600 font-black' : 'text-slate-800') }}">
                                    {{ $day }}
                                </span>

                                @if ($dayEvents->isNotEmpty())
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold {{ $dayEvents->count() >= 2 ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-indigo-100 text-indigo-800 border border-indigo-200' }} sm:inline-block hidden">
                                        {{ $dayEvents->count() }} agenda
                                    </span>
                                @endif
                            </div>

                            @if ($holidayName)
                                <div class="mb-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-100 text-rose-800 border border-rose-200 truncate flex items-center gap-1" title="{{ $holidayName }}">
                                    <svg class="w-3 h-3 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                    <span class="truncate">{{ $holidayName }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Daftar Badge Agenda Pada Tanggal Terkait -->
                        <div class="space-y-1 flex-1 overflow-y-auto max-h-[85px] sm:max-h-[105px] pr-0.5">
                            @forelse ($dayEvents as $item)
                                @php
                                    // Variasi warna background chip berdasarkan room_id
                                    $colorPalette = [
                                        0 => ['bg' => 'bg-blue-50', 'text' => 'text-blue-800', 'border' => 'border-blue-200', 'dot' => 'bg-blue-500'],
                                        1 => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-800', 'border' => 'border-emerald-200', 'dot' => 'bg-emerald-500'],
                                        2 => ['bg' => 'bg-purple-50', 'text' => 'text-purple-800', 'border' => 'border-purple-200', 'dot' => 'bg-purple-500'],
                                        3 => ['bg' => 'bg-amber-50', 'text' => 'text-amber-800', 'border' => 'border-amber-200', 'dot' => 'bg-amber-500'],
                                        4 => ['bg' => 'bg-rose-50', 'text' => 'text-rose-800', 'border' => 'border-rose-200', 'dot' => 'bg-rose-500'],
                                    ];
                                    $color = $colorPalette[$item->room_id % 5];
                                @endphp

                                <button type="button"
                                        onclick="openEventModal({{ json_encode([
                                            'event_name' => $item->event_name,
                                            'room_name' => $item->room->room_name,
                                            'room_location' => $item->room->location,
                                            'room_capacity' => $item->room->capacity,
                                            'date' => $item->formatted_date_range,
                                            'start_time' => substr($item->start_time, 0, 5),
                                            'end_time' => substr($item->end_time, 0, 5),
                                            'organizer' => $item->user->fullname,
                                            'role' => $item->user->role->role_name ?? 'User',
                                            'move_reason' => $item->move_reason,
                                            'approver' => $item->approver ? $item->approver->fullname : 'Waka / Admin',
                                        ]) }})"
                                        class="w-full text-left p-1 sm:p-1.5 rounded-lg border {{ $color['bg'] }} {{ $color['border'] }} hover:shadow-xs transition group block">
                                    
                                    <div class="flex items-center gap-1 text-[10px] font-semibold {{ $color['text'] }} truncate">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $color['dot'] }} shrink-0"></span>
                                        <span class="font-mono text-[9px]">{{ substr($item->start_time, 0, 5) }}</span>
                                        <span class="truncate font-bold">{{ $item->event_name }}</span>
                                    </div>
                                    <div class="text-[9px] text-slate-500 truncate hidden sm:block pl-2.5">
                                        {{ $item->room->room_name }}
                                    </div>
                                </button>
                            @empty
                                <!-- Tidak ada agenda -->
                            @endforelse
                        </div>

                    </div>
                @endfor

                <!-- Kotak Kosong Setelah Akhir Bulan (Agar Baris Lengkap 7 Kolom) -->
                @for ($t = 0; $t < $trailingBlankDays; $t++)
                    <div class="bg-slate-50/70 min-h-[90px] sm:min-h-[120px] p-2 select-none opacity-40">
                        <!-- Kotak kosong -->
                    </div>
                @endfor

            </div>

        </div>

    <!-- TAMPILAN 2: MODE DAFTAR AGENDA (LIST VIEW) -->
    @else
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
            <h2 class="text-base font-bold text-slate-900 mb-4">
                Daftar Agenda Terjadwal: {{ $currentMonthDate->translatedFormat('F Y') }}
            </h2>

            @if ($events->isEmpty())
                <div class="py-16 text-center text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                    <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="text-xs font-medium">Tidak ada agenda resmi yang sesuai dengan filter pencarian pada bulan ini.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($events as $item)
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 hover:bg-white hover:shadow-xs transition flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="space-y-1.5 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-700">
                                        {{ $item->room->room_name }}
                                    </span>
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                        Resmi Disetujui
                                    </span>
                                    <span class="text-xs text-slate-500 font-medium">
                                        {{ $item->room->location }}
                                    </span>
                                </div>

                                <h3 class="text-base font-bold text-slate-900">{{ $item->event_name }}</h3>

                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600">
                                    <span>Tanggal: {{ \Carbon\Carbon::parse($item->date)->translatedFormat('l, d F Y') }}</span>
                                    <span>Pukul: {{ substr($item->start_time, 0, 5) }} - {{ substr($item->end_time, 0, 5) }} WIB</span>
                                    <span>Penanggung Jawab: <strong class="text-slate-800">{{ $item->user->fullname }}</strong></span>
                                </div>

                                @if ($item->move_reason)
                                    <div class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 px-3 py-1 rounded-lg mt-1 inline-block">
                                        Ruangan telah dipindahkan oleh Waka. Alasan: <em>"{{ $item->move_reason }}"</em>
                                    </div>
                                @endif
                            </div>

                            <div class="shrink-0">
                                <button type="button"
                                        onclick="openEventModal({{ json_encode([
                                            'event_name' => $item->event_name,
                                            'room_name' => $item->room->room_name,
                                            'room_location' => $item->room->location,
                                            'room_capacity' => $item->room->capacity,
                                            'date' => \Carbon\Carbon::parse($item->date)->translatedFormat('l, d F Y'),
                                            'start_time' => substr($item->start_time, 0, 5),
                                            'end_time' => substr($item->end_time, 0, 5),
                                            'organizer' => $item->user->fullname,
                                            'role' => $item->user->role->name ?? 'User',
                                            'move_reason' => $item->move_reason,
                                            'approver' => $item->approver ? $item->approver->fullname : 'Waka / Admin',
                                        ]) }})"
                                        class="w-full md:w-auto px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl text-xs font-bold transition">
                                    Lihat Detail Acara
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

</div>

<!-- MODAL INTERAKTIF DETAIL AGENDA (READ-ONLY) -->
<div id="eventDetailModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full border border-slate-200 shadow-xl overflow-hidden animate-in fade-in zoom-in duration-150">
        
        <!-- Header Modal -->
        <div class="p-5 border-b border-slate-100 flex items-start justify-between bg-slate-50">
            <div>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 mb-1">
                    <svg class="w-3 h-3 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Agenda Resmi Disetujui</span>
                </div>
                <h3 id="modalEventName" class="text-lg font-bold text-slate-900 leading-snug">
                    Nama Agenda
                </h3>
            </div>
            <button type="button" onclick="closeEventModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition" title="Tutup">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Konten Detail Modal -->
        <div class="p-6 space-y-4 text-xs">
            
            <div class="grid grid-cols-2 gap-4">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <span class="text-slate-500 font-medium block">Ruangan</span>
                    <strong id="modalRoomName" class="text-slate-900 text-sm block mt-0.5">-</strong>
                    <span id="modalRoomLocation" class="text-[11px] text-slate-500 block">-</span>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <span class="text-slate-500 font-medium block">Kapasitas Maksimal</span>
                    <strong id="modalRoomCapacity" class="text-slate-900 text-sm block mt-0.5">-</strong>
                    <span class="text-[11px] text-slate-500 block">Peserta / Kursi</span>
                </div>
            </div>

            <div class="space-y-2 p-3 bg-blue-50/60 rounded-xl border border-blue-100">
                <div class="flex items-center justify-between">
                    <span class="text-blue-900 font-semibold">Hari & Tanggal</span>
                    <span id="modalDate" class="text-blue-950 font-bold">-</span>
                </div>
                <div class="flex items-center justify-between pt-1 border-t border-blue-100">
                    <span class="text-blue-900 font-semibold">Jam Pemakaian</span>
                    <span id="modalTime" class="text-blue-950 font-bold">- WIB</span>
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">Penanggung Jawab / Pemohon</span>
                    <span id="modalOrganizer" class="font-bold text-slate-800">-</span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">Peran / Organisasi</span>
                    <span id="modalRole" class="font-semibold text-slate-700">-</span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">Diverifikasi & Disetujui Oleh</span>
                    <span id="modalApprover" class="font-semibold text-emerald-700">-</span>
                </div>
            </div>

            <!-- Bagian Khusus: Alasan Pemindahan Ruang (Jika Ada Veto Waka) -->
            <div id="modalMoveReasonBox" class="p-3 bg-amber-50 rounded-xl border border-amber-200 hidden">
                <span class="text-amber-800 font-bold block mb-1">Catatan Pemindahan Ruang oleh Waka:</span>
                <p id="modalMoveReason" class="text-amber-900 text-xs italic leading-relaxed">-</p>
            </div>

        </div>

        <!-- Footer Modal -->
        <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="closeEventModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition">
                Tutup Jendela
            </button>
        </div>

    </div>
</div>

<script>
    function openEventModal(data) {
        document.getElementById('modalEventName').textContent = data.event_name;
        document.getElementById('modalRoomName').textContent = data.room_name;
        document.getElementById('modalRoomLocation').textContent = data.room_location;
        document.getElementById('modalRoomCapacity').textContent = data.room_capacity + ' Orang';
        document.getElementById('modalDate').textContent = data.date;
        document.getElementById('modalTime').textContent = data.start_time + ' - ' + data.end_time;
        document.getElementById('modalOrganizer').textContent = data.organizer;
        document.getElementById('modalRole').textContent = data.role;
        document.getElementById('modalApprover').textContent = data.approver;

        const moveReasonBox = document.getElementById('modalMoveReasonBox');
        if (data.move_reason && data.move_reason.trim() !== '') {
            document.getElementById('modalMoveReason').textContent = '"' + data.move_reason + '"';
            moveReasonBox.classList.remove('hidden');
        } else {
            moveReasonBox.classList.add('hidden');
        }

        document.getElementById('eventDetailModal').classList.remove('hidden');
    }

    function closeEventModal() {
        document.getElementById('eventDetailModal').classList.add('hidden');
    }

    // Tutup modal jika user menekan ESC atau klik backdrop
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEventModal();
        }
    });

    document.getElementById('eventDetailModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEventModal();
        }
    });
</script>
@endsection
