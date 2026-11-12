@extends('layouts.app')

@section('title', 'Rekap Laporan Peminjaman & Pengaduan')

@section('content')
<div class="space-y-6">

    <!-- Header Section -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200 mb-2">
                <span>Modul Eksekutif & Sarpras</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Rekap Laporan Peminjaman & Pengaduan</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Pusat data monitoring pemakaian ruangan dan keluhan fasilitas secara Harian & Bulanan.
            </p>
        </div>

        <!-- Download Action Buttons -->
        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
            <a href="{{ route('reports.export-pdf', request()->query()) }}" target="_blank"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-semibold transition shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Unduh PDF</span>
            </a>

            <a href="{{ route('reports.export-csv', request()->query()) }}"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold transition shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <span>Unduh Excel (CSV)</span>
            </a>
        </div>
    </div>

    <!-- Filter Form Bar -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('reports.index') }}" id="report-filter-form" class="space-y-4">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                <!-- Pilihan Mode Periode -->
                <div class="lg:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Mode Periode</label>
                    <div class="grid grid-cols-2 gap-1.5 p-1 bg-slate-100 rounded-xl border border-slate-200">
                        <label class="flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-lg text-xs font-semibold cursor-pointer transition {{ $period === 'daily' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            <input type="radio" name="period" value="daily" class="sr-only" {{ $period === 'daily' ? 'checked' : '' }} onchange="togglePeriodFields('daily')">
                            <span>Harian</span>
                        </label>
                        <label class="flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-lg text-xs font-semibold cursor-pointer transition {{ $period === 'monthly' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            <input type="radio" name="period" value="monthly" class="sr-only" {{ $period === 'monthly' ? 'checked' : '' }} onchange="togglePeriodFields('monthly')">
                            <span>Bulanan</span>
                        </label>
                    </div>
                </div>

                <!-- Input Tanggal Harian -->
                <div id="field-daily" class="lg:col-span-3 {{ $period === 'daily' ? '' : 'hidden' }}">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pilih Tanggal</label>
                    <input type="date" name="date" value="{{ $date }}"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                </div>

                <!-- Input Bulan & Tahun (Bulanan) -->
                <div id="field-monthly-month" class="lg:col-span-2 {{ $period === 'monthly' ? '' : 'hidden' }}">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Bulan</label>
                    <select name="month" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:bg-white">
                        @foreach ($indonesianMonths as $num => $monthName)
                            <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>
                                {{ $monthName }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="field-monthly-year" class="lg:col-span-1 {{ $period === 'monthly' ? '' : 'hidden' }}">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tahun</label>
                    <select name="year" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:bg-white">
                        @for ($y = (int)date('Y') + 1; $y >= 2024; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Jenis Laporan -->
                <div class="lg:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kategori Laporan</label>
                    <select name="type" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:bg-white">
                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>Semua (Peminjaman & Pengaduan)</option>
                        <option value="events" {{ $type === 'events' ? 'selected' : '' }}>Hanya Peminjaman Ruangan</option>
                        <option value="complaints" {{ $type === 'complaints' ? 'selected' : '' }}>Hanya Pengaduan Sarpras</option>
                    </select>
                </div>

                <!-- Filter Ruangan -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Ruangan</label>
                    <select name="room_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:bg-white">
                        <option value="">Semua Ruangan</option>
                        @foreach ($rooms as $r)
                            <option value="{{ $r->id }}" {{ $room_id == $r->id ? 'selected' : '' }}>{{ $r->room_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Tombol Submit Filter -->
                <div class="lg:col-span-1">
                    <button type="submit"
                        class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold transition shadow-xs flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        <span>Filter</span>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2 border-t border-slate-100 text-xs text-slate-500">
                <span class="font-medium text-slate-700">Filter Aktif:</span>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 font-semibold border border-blue-200">
                    Periode: {{ $periodLabel }}
                </span>
                @if ($room_id)
                    @php $currentRoom = $rooms->firstWhere('id', $room_id); @endphp
                    @if ($currentRoom)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 font-semibold border border-slate-200">
                            Ruangan: {{ $currentRoom->room_name }}
                        </span>
                    @endif
                @endif
            </div>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Peminjaman -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Peminjaman</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-slate-900">{{ $stats['total_events'] }}</div>
            <div class="flex items-center gap-2 text-[11px] text-slate-500 mt-1">
                <span class="text-emerald-600 font-semibold">{{ $stats['approved_events'] }} Disetujui</span> •
                <span class="text-amber-600 font-semibold">{{ $stats['pending_events'] }} Pending</span> •
                <span class="text-rose-600 font-semibold">{{ $stats['rejected_events'] }} Ditolak</span>
            </div>
        </div>

        <!-- Peminjaman Disetujui -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Peminjaman Selesai/ACC</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-emerald-700">{{ $stats['approved_events'] }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Kegiatan berjalan resmi di ruangan</div>
        </div>

        <!-- Total Pengaduan -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Pengaduan</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-slate-900">{{ $stats['total_complaints'] }}</div>
            <div class="flex items-center gap-2 text-[11px] text-slate-500 mt-1">
                <span class="text-rose-600 font-semibold">{{ $stats['pending_complaints'] }} Belum</span> •
                <span class="text-amber-600 font-semibold">{{ $stats['progress_complaints'] }} Part</span> •
                <span class="text-emerald-600 font-semibold">{{ $stats['resolved_complaints'] }} Selesai</span>
            </div>
        </div>

        <!-- Pengaduan Tuntas -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-purple-600 uppercase tracking-wider">Tingkat Perbaikan</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
            </div>
            @php
                $repairRate = $stats['total_complaints'] > 0 ? round(($stats['resolved_complaints'] / $stats['total_complaints']) * 100) : 100;
            @endphp
            <div class="text-2xl font-bold text-purple-700">{{ $repairRate }}%</div>
            <div class="text-[11px] text-slate-500 mt-1">{{ $stats['resolved_complaints'] }} dari {{ $stats['total_complaints'] }} masalah terselesaikan</div>
        </div>
    </div>

    <!-- Table 1: Peminjaman Ruangan -->
    @if ($type === 'all' || $type === 'events')
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-blue-600"></div>
                <h2 class="font-bold text-slate-900 text-base">Rekap Peminjaman Ruangan</h2>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    {{ $events->count() }} Data
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">No</th>
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4">Ruangan</th>
                        <th class="py-3 px-4">Sesi / Jam</th>
                        <th class="py-3 px-4">Agenda Kegiatan</th>
                        <th class="py-3 px-4">Peminjam</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Catatan / Alasan Penolakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse ($events as $index => $event)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3.5 px-4 font-semibold text-slate-400">{{ $index + 1 }}</td>
                        <td class="py-3.5 px-4 font-medium text-slate-900 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($event->date)->translatedFormat('d M Y') }}
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="font-bold text-slate-800">{{ $event->room ? $event->room->room_name : '-' }}</span>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if ($event->session_label)
                                <div class="font-semibold text-blue-700">{{ $event->session_label }}</div>
                                <div class="text-[11px] text-slate-500">{{ substr($event->start_time, 0, 5) }} - {{ substr($event->end_time, 0, 5) }}</div>
                            @else
                                <span class="font-medium text-slate-700">{{ substr($event->start_time, 0, 5) }} - {{ substr($event->end_time, 0, 5) }}</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-semibold text-slate-900">{{ $event->event_name }}</div>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-medium text-slate-800">{{ $event->user ? $event->user->fullname : '-' }}</div>
                            <div class="text-[11px] text-slate-400">{{ $event->user ? $event->user->role->role_name : '' }}</div>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if ($event->status === 'approved')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                    Disetujui
                                </span>
                            @elseif ($event->status === 'rejected')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                                    <svg class="w-3 h-3 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                    Ditolak
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                    <svg class="w-3 h-3 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
                                    Pending
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-xs">
                            @if ($event->alasan_penolakan)
                                <div class="p-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-[11px]">
                                    <span class="font-bold">Alasan Tolak:</span> {{ $event->alasan_penolakan }}
                                </div>
                            @elseif ($event->move_reason)
                                <div class="p-2 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-[11px]">
                                    <span class="font-bold">Pemindahan:</span> {{ $event->move_reason }}
                                </div>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-500">
                            Tidak ada data peminjaman ruangan yang sesuai dengan filter periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Table 2: Pengaduan Kerusakan Sarpras -->
    @if ($type === 'all' || $type === 'complaints')
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                <h2 class="font-bold text-slate-900 text-base">Rekap Pengaduan Kerusakan Sarpras</h2>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                    {{ $complaints->count() }} Data
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">No</th>
                        <th class="py-3 px-4">Tanggal Pengaduan</th>
                        <th class="py-3 px-4">Ruangan</th>
                        <th class="py-3 px-4">Terkait Peminjaman</th>
                        <th class="py-3 px-4">Pelapor</th>
                        <th class="py-3 px-4">Deskripsi Kerusakan</th>
                        <th class="py-3 px-4">Status Perbaikan</th>
                        <th class="py-3 px-4">Penanggung Jawab</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse ($complaints as $index => $c)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3.5 px-4 font-semibold text-slate-400">{{ $index + 1 }}</td>
                        <td class="py-3.5 px-4 font-medium text-slate-900 whitespace-nowrap">
                            {{ $c->reported_at ? \Carbon\Carbon::parse($c->reported_at)->translatedFormat('d M Y H:i') : ($c->created_at ? \Carbon\Carbon::parse($c->created_at)->translatedFormat('d M Y H:i') : '-') }}
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="font-bold text-slate-800">{{ $c->room ? $c->room->room_name : '-' }}</span>
                        </td>
                        <td class="py-3.5 px-4">
                            @if ($c->event)
                                <div class="font-semibold text-blue-700">{{ $c->event->event_name }}</div>
                                <div class="text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($c->event->date)->translatedFormat('d M Y') }}</div>
                            @else
                                <span class="text-slate-400 italic">Pengaduan Reguler</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-medium text-slate-800">{{ $c->reporter ? $c->reporter->fullname : '-' }}</div>
                            <div class="text-[11px] text-slate-400">{{ $c->reporter ? $c->reporter->role->role_name : '' }}</div>
                        </td>
                        <td class="py-3.5 px-4">
                            <p class="text-slate-700 max-w-xs">{{ $c->description }}</p>
                            @if ($c->photo_url)
                                <button type="button"
                                    onclick="openPhotoModal({
                                        url: '{{ $c->photo_url }}',
                                        room: '{{ addslashes($c->room ? $c->room->room_name : '-') }}',
                                        location: '{{ addslashes($c->room ? $c->room->location : '-') }}',
                                        reporter: '{{ addslashes($c->reporter ? $c->reporter->fullname : '-') }}',
                                        pic: '{{ addslashes($c->handler ? $c->handler->fullname : ($c->room && $c->room->pic ? $c->room->pic->fullname : '-')) }}',
                                        status: '{{ addslashes($c->status_repair) }}',
                                        statusClass: '{{ $c->status_repair === 'Sudah Diperbaiki' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : ($c->status_repair === 'Menunggu Spare Part' ? 'bg-amber-100 text-amber-700 border-amber-200' : 'bg-rose-100 text-rose-700 border-rose-200') }}',
                                        time: '{{ $c->reported_at ? \Carbon\Carbon::parse($c->reported_at)->translatedFormat('d F Y, H:i') . ' WIB' : '-' }}',
                                        description: '{{ addslashes($c->description) }}'
                                    })"
                                    class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-semibold text-blue-600 hover:text-blue-800 bg-blue-50 px-2 py-0.5 rounded border border-blue-200 transition">
                                    <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    <span>Lihat Foto</span>
                                </button>
                            @else
                                <span class="mt-1.5 inline-block text-[11px] text-slate-400 italic">Tanpa Foto</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if ($c->status_repair === 'Sudah Diperbaiki')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                    Sudah Diperbaiki
                                </span>
                            @elseif ($c->status_repair === 'Menunggu Spare Part')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                    Menunggu Spare Part
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                    Belum Diperbaiki
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-slate-700 font-medium whitespace-nowrap">
                            {{ $c->handler ? $c->handler->fullname : ($c->room && $c->room->pic ? $c->room->pic->fullname : '-') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-500">
                            Tidak ada data pengaduan kerusakan sarpras yang sesuai dengan filter periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

@push('scripts')
<script>
    function togglePeriodFields(period) {
        const dailyField = document.getElementById('field-daily');
        const monthField = document.getElementById('field-monthly-month');
        const yearField = document.getElementById('field-monthly-year');

        if (period === 'daily') {
            dailyField.classList.remove('hidden');
            monthField.classList.add('hidden');
            yearField.classList.add('hidden');
        } else {
            dailyField.classList.add('hidden');
            monthField.classList.remove('hidden');
            yearField.classList.remove('hidden');
        }
    }
</script>
@endpush

@include('complaints.photo-modal')
@endsection
