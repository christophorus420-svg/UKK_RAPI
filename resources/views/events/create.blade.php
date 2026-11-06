@extends('layouts.app')

@section('title', 'Ajukan Peminjaman Ruang')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
        <div class="mb-6">
            <a href="{{ route('events.index') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold inline-flex items-center gap-1 mb-2">
                &larr; Kembali ke Daftar Agenda
            </a>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Formulir Pengajuan Peminjaman Ruang</h1>
            <p class="text-xs text-slate-500 mt-1">
                Sistem secara otomatis mendeteksi bentrok jadwal. Pastikan ruangan dan waktu belum dipesan oleh kegiatan lain.
            </p>
        </div>

        <form method="POST" action="{{ route('events.store') }}" id="event-form" class="space-y-4">
            @csrf

            <!-- Nama Kegiatan -->
            <div>
                <label for="event_name" class="block text-xs font-semibold text-slate-700 mb-1">
                    Nama Kegiatan / Agenda <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="event_name" name="event_name" value="{{ old('event_name') }}" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                    placeholder="Contoh: Sosialisasi Praktik Kerja Lapangan (PKL), Rapat MGMP RPL">
            </div>

            <!-- Pilihan Ruangan -->
            <div>
                <label for="room_id" class="block text-xs font-semibold text-slate-700 mb-1">
                    Pilih Ruangan Fasilitas <span class="text-rose-500">*</span>
                </label>
                <select id="room_id" name="room_id" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                    <option value="">-- Pilih Ruangan Tersedia --</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                            {{ $room->room_name }} (Kapasitas: {{ $room->capacity }} orang • {{ $room->location }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Tanggal Mulai & Tanggal Selesai (Dukungan Multi-Hari) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="date" class="block text-xs font-semibold text-slate-700 mb-1">
                        Tanggal Mulai Kegiatan <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" id="date" name="date" min="{{ date('Y-m-d') }}" value="{{ old('date', date('Y-m-d')) }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="end_date" class="block text-xs font-semibold text-slate-700">
                            Tanggal Selesai (Multi-Hari)
                        </label>
                        <span class="text-[10px] text-slate-400">Opsional / Lebih dari 1 hari</span>
                    </div>
                    <input type="date" id="end_date" name="end_date" min="{{ date('Y-m-d') }}" value="{{ old('end_date') }}"
                        placeholder="Pilih jika lebih dari 1 hari"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                    <p class="text-[10px] text-slate-400 mt-1">Kosongkan jika hanya 1 hari (sama dengan tanggal mulai).</p>
                </div>
            </div>

                <!-- Pilihan Berbasis Sesi -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700">
                            Pilih Sesi Peminjaman <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-[11px] text-blue-600 font-medium">Sistem Terbagi ke Sesi Jam Pelajaran</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 max-h-[380px] overflow-y-auto p-1 border border-slate-100 rounded-xl bg-slate-50/50" id="session-cards-container">
                        @foreach ($sessions as $session)
                            @php
                                $startTimeFormatted = substr($session->start_time, 0, 5);
                                $endTimeFormatted = substr($session->end_time, 0, 5);
                                $isSelected = old('session_id') == $session->id || ($loop->first && !old('session_id'));
                            @endphp
                            <label class="session-card relative flex items-center p-3 rounded-xl border cursor-pointer transition select-none
                                {{ $isSelected ? 'border-blue-600 bg-blue-50/60 ring-2 ring-blue-500/20' : 'border-slate-200 bg-white hover:bg-slate-50' }}">
                                <input type="radio" name="session_id" value="{{ $session->id }}"
                                    data-start="{{ $startTimeFormatted }}" data-end="{{ $endTimeFormatted }}"
                                    {{ $isSelected ? 'checked' : '' }}
                                    class="sr-only session-radio">
                                <div class="flex items-center justify-between w-full">
                                    <div>
                                        <div class="font-bold text-xs text-slate-900 session-name">{{ $session->session_name }}</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1 font-medium">
                                            <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span>{{ $startTimeFormatted }} - {{ $endTimeFormatted }} WIB</span>
                                        </div>
                                    </div>
                                    <div class="radio-indicator w-4 h-4 rounded-full border flex items-center justify-center shrink-0 ml-2
                                        {{ $isSelected ? 'border-blue-600 bg-blue-600' : 'border-slate-300' }}">
                                        <div class="w-1.5 h-1.5 rounded-full bg-white {{ $isSelected ? '' : 'hidden' }}"></div>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <!-- Hidden inputs untuk jam mulai dan selesai agar sinkron dengan sistem -->
                    <input type="hidden" id="start_time" name="start_time" value="{{ old('start_time', $sessions->first()?->start_time ? substr($sessions->first()->start_time, 0, 5) : '07:00') }}">
                    <input type="hidden" id="end_time" name="end_time" value="{{ old('end_time', $sessions->first()?->end_time ? substr($sessions->first()->end_time, 0, 5) : '07:45') }}">
                </div>
            </div>

            <!-- Kotak Indikator Ketersediaan Real-Time -->
            <div id="availability-box" class="p-3.5 rounded-xl border text-xs hidden transition">
                <div class="flex items-center gap-2">
                    <span id="availability-icon" class="inline-flex items-center shrink-0"></span>
                    <span id="availability-text" class="font-medium"></span>
                </div>
            </div>

            <!-- Catatan Hak Akses Sesuai Role -->
            <div class="p-3.5 bg-blue-50/60 rounded-xl border border-blue-100 text-xs text-blue-900 space-y-1">
                <div class="font-bold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Catatan Aturan Penjadwalan Berbasis Sesi:</span>
                </div>
                <ul class="list-disc list-inside text-blue-800 space-y-0.5 text-[11px]">
                    <li>Peminjaman dilakukan berbasis <strong>Sesi</strong> untuk mengoptimalkan rotasi ruangan antar kelas/kegiatan.</li>
                    <li>Peminjaman oleh Guru / Organisasi akan berstatus <strong>Pending</strong> menunggu verifikasi Waka Sarpras.</li>
                    <li>Sistem backend secara otomatis memvalidasi bentrok jadwal berdasarkan ruangan, tanggal, dan ID sesi.</li>
                </ul>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('events.index') }}" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Batal
                </a>
                <button type="submit" id="submit-btn"
                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-2">
                    <span>Kirim Pengajuan Agenda</span>
                </button>
            </div>
        </form>
    </div>

</div>

<!-- Script Pengecekan Ketersediaan Real-Time Berbasis Sesi via AJAX -->
<script>
    const roomSelect = document.getElementById('room_id');
    const dateInput = document.getElementById('date');
    const endDateInput = document.getElementById('end_date');
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const box = document.getElementById('availability-box');
    const icon = document.getElementById('availability-icon');
    const text = document.getElementById('availability-text');
    const sessionRadios = document.querySelectorAll('.session-radio');
    const sessionCards = document.querySelectorAll('.session-card');
    const submitBtn = document.getElementById('submit-btn');

    dateInput.addEventListener('change', () => {
        if (dateInput.value) {
            endDateInput.min = dateInput.value;
            if (endDateInput.value && endDateInput.value < dateInput.value) {
                endDateInput.value = dateInput.value;
            }
        }
    });

    function getSelectedSession() {
        const checked = document.querySelector('.session-radio:checked');
        return checked ? checked : null;
    }

    function updateCardStyles() {
        sessionCards.forEach(card => {
            const radio = card.querySelector('.session-radio');
            const indicator = card.querySelector('.radio-indicator');
            const dot = indicator.querySelector('div');

            if (radio.checked) {
                card.className = 'session-card relative flex items-center p-3.5 rounded-xl border cursor-pointer transition select-none border-blue-600 bg-blue-50/60 ring-2 ring-blue-500/20';
                indicator.className = 'radio-indicator w-4 h-4 rounded-full border flex items-center justify-center border-blue-600 bg-blue-600';
                dot.classList.remove('hidden');
                startTimeInput.value = radio.dataset.start;
                endTimeInput.value = radio.dataset.end;
            } else {
                card.className = 'session-card relative flex items-center p-3.5 rounded-xl border cursor-pointer transition select-none border-slate-200 bg-white hover:bg-slate-50';
                indicator.className = 'radio-indicator w-4 h-4 rounded-full border flex items-center justify-center border-slate-300';
                dot.classList.add('hidden');
            }
        });
    }

    async function cekKetersediaan() {
        const roomId = roomSelect.value;
        const date = dateInput.value;
        const endDate = endDateInput.value;
        const selectedRadio = getSelectedSession();

        if (!roomId || !date || !selectedRadio) {
            box.classList.add('hidden');
            return;
        }

        const sessionId = selectedRadio.value;
        const start = selectedRadio.dataset.start;
        const end = selectedRadio.dataset.end;

        try {
            const endDateParam = endDate ? `&end_date=${endDate}` : '';
            const res = await fetch(`{{ route('events.check-availability') }}?room_id=${roomId}&date=${date}${endDateParam}&session_id=${sessionId}&start_time=${start}&end_time=${end}`);
            const data = await res.json();

            box.classList.remove('hidden');
            if (data.available) {
                box.className = 'p-3.5 rounded-xl border text-xs bg-emerald-50 text-emerald-800 border-emerald-200';
                icon.innerHTML = '<svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>';
                text.textContent = data.message || 'Jadwal Sesi Aman: Ruangan dan sesi tersebut tersedia untuk dipinjam.';
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                box.className = 'p-3.5 rounded-xl border text-xs bg-rose-50 text-rose-800 border-rose-200 font-semibold';
                icon.innerHTML = '<svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>';
                text.textContent = data.message;
            }
        } catch (e) {
            // Jaringan gagal ringan
        }
    }

    sessionRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            updateCardStyles();
            cekKetersediaan();
        });
    });

    [roomSelect, dateInput, endDateInput].forEach(el => {
        el.addEventListener('change', cekKetersediaan);
    });

    updateCardStyles();
    if (roomSelect.value && dateInput.value) {
        cekKetersediaan();
    }
</script>
@endsection
