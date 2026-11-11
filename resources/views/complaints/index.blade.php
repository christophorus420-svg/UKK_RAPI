@extends('layouts.app')

@section('title', 'Pengaduan Kerusakan Sarpras')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Button -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 mb-2">
                <span>Pemeliharaan Sarana Prasarana</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pengaduan Kerusakan Sarpras</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Alur pelaporan bukti fisik kerusakan sarpras dan pemantauan siklus perbaikan oleh PIC Ruang.
            </p>
        </div>

        @if (Auth::user()->isGuru() || Auth::user()->isAdmin())
            <a href="{{ route('complaints.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-semibold transition shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Laporkan Kerusakan Baru</span>
            </a>
        @endif
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('complaints.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <div class="sm:col-span-5">
                <input type="text" name="q" value="{{ request('q') }}"
                    placeholder="Cari deskripsi, ruangan, atau pelapor..."
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
            <div class="sm:col-span-3">
                <select name="status_repair" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white">
                    <option value="">Semua Status Siklus</option>
                    <option value="Belum Diperbaiki" {{ request('status_repair') === 'Belum Diperbaiki' ? 'selected' : '' }}>Belum Diperbaiki</option>
                    <option value="Menunggu Spare Part" {{ request('status_repair') === 'Menunggu Spare Part' ? 'selected' : '' }}>Menunggu Spare Part</option>
                    <option value="Sudah Diperbaiki" {{ request('status_repair') === 'Sudah Diperbaiki' ? 'selected' : '' }}>Sudah Diperbaiki</option>
                </select>
            </div>
            <div class="sm:col-span-1 flex gap-1">
                <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold transition" title="Filter Laporan">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Grid Kartu Pengaduan Sarpras -->
    @if ($complaints->isEmpty())
        <div class="bg-white rounded-2xl p-12 text-center text-xs text-slate-400 border border-dashed border-slate-200">
            Tidak ada laporan kerusakan sarpras yang ditemukan.
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($complaints as $item)
                @php
                    $isPicRuang = (Auth::id() === $item->room->pic_id);
                    $isAdmin = Auth::user()->isAdmin();
                    $bisaKelolaStatus = ($isPicRuang && !$isAdmin);
                    $bisaHapus = ($isAdmin || (int)Auth::id() === (int)$item->reporter_id || (int)Auth::id() === (int)$item->room->pic_id);

                    $badgeStatusClass = match($item->status_repair) {
                        'Sudah Diperbaiki' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                        'Menunggu Spare Part' => 'bg-amber-100 text-amber-700 border-amber-200',
                        default => 'bg-rose-100 text-rose-700 border-rose-200',
                    };
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs hover:shadow-sm transition flex flex-col justify-between overflow-hidden">
                    <div>
                        <!-- Foto Bukti Fisik Kerusakan -->
                        @if ($item->photo_url)
                            <div class="h-44 bg-slate-900/5 overflow-hidden relative group cursor-pointer"
                                onclick="openPhotoModal({
                                    url: '{{ $item->photo_url }}',
                                    room: '{{ addslashes($item->room->room_name) }}',
                                    location: '{{ addslashes($item->room->location) }}',
                                    reporter: '{{ addslashes($item->reporter->fullname) }}',
                                    pic: '{{ addslashes($item->room->pic->fullname) }}',
                                    status: '{{ addslashes($item->status_repair) }}',
                                    statusClass: '{{ $badgeStatusClass }}',
                                    time: '{{ \Carbon\Carbon::parse($item->reported_at)->translatedFormat('d F Y, H:i') }} WIB',
                                    description: '{{ addslashes($item->description) }}'
                                })"
                                title="Klik untuk melihat foto ukuran penuh">
                                <img src="{{ $item->photo_url }}" alt="Bukti Fisik Kerusakan"
                                    class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                    onerror="this.onerror=null; this.src='{{ asset('images/placeholder-no-image.svg') }}';">
                                
                                <!-- Hover Zoom Overlay -->
                                <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition duration-200 flex flex-col items-center justify-center gap-1.5 text-white">
                                    <div class="w-10 h-10 rounded-full bg-white/20 backdrop-blur-xs flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                        </svg>
                                    </div>
                                    <span class="text-xs font-semibold drop-shadow-xs">Klik untuk Memperbesar</span>
                                </div>

                                <div class="absolute bottom-2 right-2 bg-slate-900/75 backdrop-blur-xs text-white text-[10px] font-medium px-2.5 py-1 rounded-lg flex items-center gap-1 shadow-xs">
                                    <svg class="w-3 h-3 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>Bukti Foto</span>
                                </div>
                            </div>
                        @else
                            <div class="h-28 bg-slate-50 flex flex-col items-center justify-center text-slate-400 text-xs border-b border-slate-100">
                                <svg class="w-8 h-8 text-slate-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span>Tanpa Lampiran Foto</span>
                            </div>
                        @endif

                        <div class="p-5">
                            <!-- Header Card: Lokasi Ruang & Status -->
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div>
                                    <h3 class="font-bold text-slate-900 text-sm">{{ $item->room->room_name }}</h3>
                                    <p class="text-[11px] text-slate-400">{{ $item->room->location }}</p>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span class="px-2.5 py-0.5 rounded-lg font-semibold text-xs border {{ $badgeStatusClass }}">
                                        {{ $item->status_repair }}
                                    </span>
                                    @if ($bisaHapus)
                                        <form method="POST" action="{{ route('complaints.destroy', $item) }}" class="inline"
                                            onsubmit="return confirm('PERINGATAN HAPUS PENGADUAN:\nApakah Anda yakin ingin menghapus data laporan kerusakan di \'{{ addslashes($item->room->room_name) }}\' secara manual?\n\nTindakan ini bersifat permanen dan data yang dihapus tidak dapat dipulihkan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Pengaduan Secara Manual">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            @if ($item->event)
                                <div class="mb-2 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 text-[11px] font-medium w-full">
                                    <svg class="w-3.5 h-3.5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="truncate">Terkait Agenda: <strong>{{ $item->event->event_name }}</strong> (#{{ $item->event->id }})</span>
                                </div>
                            @endif

                            <!-- Deskripsi Kerusakan -->
                            <p class="text-xs text-slate-700 leading-relaxed mt-2 bg-slate-50/80 p-3 rounded-xl border border-slate-100">
                                "{{ $item->description }}"
                            </p>

                            @if ($item->photo_url)
                                <div class="mt-2.5">
                                    <button type="button"
                                        onclick="openPhotoModal({
                                            url: '{{ $item->photo_url }}',
                                            room: '{{ addslashes($item->room->room_name) }}',
                                            location: '{{ addslashes($item->room->location) }}',
                                            reporter: '{{ addslashes($item->reporter->fullname) }}',
                                            pic: '{{ addslashes($item->room->pic->fullname) }}',
                                            status: '{{ addslashes($item->status_repair) }}',
                                            statusClass: '{{ $badgeStatusClass }}',
                                            time: '{{ \Carbon\Carbon::parse($item->reported_at)->translatedFormat('d F Y, H:i') }} WIB',
                                            description: '{{ addslashes($item->description) }}'
                                        })"
                                        class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl text-xs font-semibold transition">
                                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        <span>Lihat Bukti Foto</span>
                                    </button>
                                </div>
                            @endif

                            @if ($item->status_repair === 'Sudah Diperbaiki')
                                <div class="mt-3 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs">
                                    <div class="flex items-center justify-between text-emerald-900 font-bold mb-1">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            <span>Hasil & Keterangan Perbaikan:</span>
                                        </span>
                                        @if ($item->repaired_at)
                                            <span class="text-[10px] text-emerald-700 font-semibold font-mono">{{ \Carbon\Carbon::parse($item->repaired_at)->translatedFormat('d M Y, H:i') }} WIB</span>
                                        @endif
                                    </div>
                                    <p class="text-slate-700 italic">"{{ $item->repair_notes ?: 'Fasilitas telah diperbaiki dan dapat digunakan normal kembali.' }}"</p>
                                    <div class="text-[10px] text-emerald-800 mt-1">Teknisi/PIC: <strong>{{ $item->handler?->fullname ?? $item->room->pic->fullname }}</strong></div>
                                </div>
                                <div class="mt-2 flex items-center justify-between text-[11px] bg-slate-100 text-slate-600 px-2.5 py-1.5 rounded-lg border border-slate-200">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span>Otomatis dihapus sistem dalam:</span>
                                    </span>
                                    <span class="font-bold text-slate-800 font-mono">{{ $item->auto_delete_remaining ?? '24 jam' }}</span>
                                </div>
                            @endif

                            <!-- Info Pelapor & Waktu -->
                            <div class="mt-3 text-[11px] text-slate-500 space-y-1">
                                <div class="flex items-center justify-between">
                                    <span>Pelapor: <strong class="text-slate-700">{{ $item->reporter->fullname }}</strong></span>
                                    <span>{{ \Carbon\Carbon::parse($item->reported_at)->diffForHumans() }}</span>
                                </div>
                                <div>
                                    PIC Ruangan: <strong class="text-slate-700">{{ $item->room->pic->fullname }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Card: Pembaruan Status PIC & Eskalasi WA Admin -->
                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex flex-col gap-2.5">
                        @if ($bisaKelolaStatus)
                            <!-- Khusus Staf PIC Ruangan: Form Pembaruan Siklus Perbaikan -->
                            <div class="space-y-2">
                                <div class="text-[10px] font-semibold text-slate-500 uppercase flex items-center justify-between">
                                    <span>Status Siklus Perbaikan (PIC):</span>
                                </div>
                                <form method="POST" action="{{ route('complaints.update-status', $item) }}" class="space-y-2">
                                    @csrf
                                    @method('PATCH')
                                    <div class="flex items-center gap-1.5">
                                        <select name="status_repair" id="select-status-{{ $item->id }}"
                                            onchange="toggleRepairNotes('{{ $item->id }}')"
                                            class="flex-1 px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-blue-500">
                                            <option value="Belum Diperbaiki" {{ $item->status_repair === 'Belum Diperbaiki' ? 'selected' : '' }}>Belum Diperbaiki</option>
                                            <option value="Menunggu Spare Part" {{ $item->status_repair === 'Menunggu Spare Part' ? 'selected' : '' }}>Menunggu Spare Part</option>
                                            <option value="Sudah Diperbaiki" {{ $item->status_repair === 'Sudah Diperbaiki' ? 'selected' : '' }}>Sudah Diperbaiki</option>
                                        </select>
                                        <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold transition shrink-0 shadow-xs">
                                            Simpan
                                        </button>
                                    </div>

                                    <!-- Form Keterangan Perbaikan Khusus saat Sudah Diperbaiki (REVISI PIC 4) -->
                                    <div id="repair-notes-box-{{ $item->id }}" class="{{ $item->status_repair === 'Sudah Diperbaiki' ? '' : 'hidden' }} p-2.5 bg-emerald-50 rounded-xl border border-emerald-200 space-y-2">
                                        <div>
                                            <label class="block text-[10px] font-bold text-emerald-900 mb-0.5">
                                                Keterangan Perbaikan (Apa yang diperbaiki?):
                                            </label>
                                            <textarea name="repair_notes" rows="2"
                                                placeholder="Contoh: Mengganti kabel adaptor projector dan membersihkan saringan kipas..."
                                                class="w-full px-2.5 py-1.5 bg-white border border-emerald-300 rounded-lg text-xs focus:ring-2 focus:ring-emerald-500">{{ old('repair_notes', $item->repair_notes) }}</textarea>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-emerald-900 mb-0.5">
                                                Waktu/Tanggal Selesai Perbaikan:
                                            </label>
                                            <input type="datetime-local" name="repaired_at"
                                                value="{{ $item->repaired_at ? \Carbon\Carbon::parse($item->repaired_at)->format('Y-m-d\TH:i') : date('Y-m-d\TH:i') }}"
                                                class="w-full px-2.5 py-1.5 bg-white border border-emerald-300 rounded-lg text-xs focus:ring-2 focus:ring-emerald-500">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @elseif ($isAdmin)
                            <!-- Khusus Admin: Pemantauan & Kirim Peringatan WhatsApp ke PIC -->
                            <div class="space-y-2">
                                <div class="text-[11px] text-slate-600 leading-tight">
                                    <span class="font-bold text-slate-800">PIC Penanggung Jawab:</span> {{ $item->room->pic->fullname }}
                                    <span class="block text-[10px] text-slate-400 mt-0.5">* Status perbaikan fisik dikelola langsung oleh PIC ruangan.</span>
                                </div>

                                @if ($item->status_repair === 'Belum Diperbaiki')
                                    @if ($item->isOverdue())
                                        <div class="px-2 py-1 rounded bg-rose-50 border border-rose-200 text-rose-700 text-[11px] font-semibold flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></span>
                                            <span>Keterlambatan: Melewati batas waktu penanganan (&ge; 2 hari)</span>
                                        </div>
                                    @endif
                                    <a href="{{ $item->whatsapp_escalation_url }}" target="_blank"
                                        class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl font-semibold text-xs shadow-xs transition" title="Kirim Peringatan WhatsApp API Resmi ke PIC Ruang">
                                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-5.705 1.554zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                                        </svg>
                                        <span>Peringatkan PIC via WhatsApp</span>
                                    </a>
                                @elseif ($item->status_repair === 'Menunggu Spare Part')
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="text-[11px] text-amber-700 font-semibold bg-amber-50 px-2 py-1 rounded-lg border border-amber-200 flex-1 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span>Menunggu pengadaan spare part oleh PIC</span>
                                        </div>
                                        <a href="{{ $item->whatsapp_escalation_url }}" target="_blank"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium text-[11px] shadow-xs transition" title="Follow-up pengadaan spare part ke PIC">
                                            <span>WhatsApp</span>
                                        </a>
                                    </div>
                                @else
                                    <div class="text-[11px] text-emerald-700 font-semibold bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200 text-center flex items-center justify-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Telah diselesaikan oleh PIC ({{ $item->handler?->fullname ?? $item->room->pic->fullname }})</span>
                                    </div>
                                @endif
                            </div>
                        @else
                            <!-- User biasa (Guru / Guest) -->
                            <div class="text-[11px] text-slate-400 italic">
                                * Status penanganan fasilitas diproses langsung oleh staf PIC ruangan ({{ $item->room->pic->fullname }}).
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if ($complaints->hasPages())
            <div class="p-4 bg-white rounded-2xl border border-slate-200">
                {{ $complaints->links() }}
            </div>
        @endif
    @endif

</div>

@include('complaints.photo-modal')

<script>
    function toggleRepairNotes(id) {
        const select = document.getElementById('select-status-' + id);
        const box = document.getElementById('repair-notes-box-' + id);
        if (select && box) {
            if (select.value === 'Sudah Diperbaiki') {
                box.classList.remove('hidden');
            } else {
                box.classList.add('hidden');
            }
        }
    }
</script>
@endsection
