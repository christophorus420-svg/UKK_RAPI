@php
    $isEdit = isset($event) && $event !== null;
    $minDate = $isEdit && $event->date && $event->date->format('Y-m-d') < date('Y-m-d')
        ? $event->date->format('Y-m-d')
        : date('Y-m-d');

    if (old('session_ids') !== null) {
        $selectedSessionIds = collect((array) old('session_ids', []))
            ->map(fn ($id) => (int) $id)
            ->all();
    } elseif (old('session_id') !== null) {
        $selectedSessionIds = [(int) old('session_id')];
    } elseif ($isEdit) {
        $selectedSessionIds = $event->matched_session_ids;
    } else {
        $selectedSessionIds = $sessions->isNotEmpty() ? [(int) $sessions->first()->id] : [];
    }

    $selectedSessions = $sessions->whereIn('id', $selectedSessionIds)->values();
    $defaultStart = old('start_time', $selectedSessions->first()
        ? substr((string) $selectedSessions->first()->start_time, 0, 5)
        : ($isEdit ? substr((string) $event->start_time, 0, 5) : ''));
    $defaultEnd = old('end_time', $selectedSessions->last()
        ? substr((string) $selectedSessions->last()->end_time, 0, 5)
        : ($isEdit ? substr((string) $event->end_time, 0, 5) : ''));
@endphp

<form method="POST" action="{{ $formAction }}" id="event-form" class="space-y-4">
    @csrf
    @if (strtoupper($formMethod ?? 'POST') !== 'POST')
        @method($formMethod)
    @endif

    @if ($isEdit && $event->status === 'approved' && !Auth::user()->isAdmin())
        <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 flex items-start gap-2.5">
            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div>
                <div class="font-bold">Perhatian Edit Agenda Disetujui</div>
                <p class="mt-0.5 text-[11px] text-amber-800">
                    Karena peminjaman ini sebelumnya sudah disetujui, menyimpan perubahan akan mengirim ulang agenda untuk verifikasi Waka Sarpras (status menjadi <strong>Pending</strong>).
                </p>
            </div>
        </div>
    @endif

    <!-- Nama Kegiatan -->
    <div>
        <label for="event_name" class="block text-xs font-semibold text-slate-700 mb-1">
            Nama Kegiatan / Agenda <span class="text-rose-500">*</span>
        </label>
        <input type="text" id="event_name" name="event_name" value="{{ old('event_name', $event?->event_name) }}" required
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
                <option value="{{ $room->id }}" {{ old('room_id', $event?->room_id) == $room->id ? 'selected' : '' }}>
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
            <input type="date" id="date" name="date" min="{{ $minDate }}"
                value="{{ old('date', $event?->date?->format('Y-m-d') ?? date('Y-m-d')) }}" required
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
        </div>
        <div>
            <div class="flex items-center justify-between mb-1">
                <label for="end_date" class="block text-xs font-semibold text-slate-700">
                    Tanggal Selesai (Multi-Hari)
                </label>
                <span class="text-[10px] text-slate-400">Opsional / Lebih dari 1 hari</span>
            </div>
            <input type="date" id="end_date" name="end_date" min="{{ $minDate }}"
                value="{{ old('end_date', $event?->end_date?->format('Y-m-d')) }}"
                placeholder="Pilih jika lebih dari 1 hari"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
            <p class="text-[10px] text-slate-400 mt-1">Kosongkan jika hanya 1 hari (sama dengan tanggal mulai).</p>
        </div>
    </div>

    <!-- Pilihan Berbasis Multi-Sesi (Checkbox) -->
    <div>
        <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
            <label class="block text-xs font-semibold text-slate-700">
                Pilih Sesi Peminjaman (Bisa Lebih dari Satu) <span class="text-rose-500">*</span>
            </label>
            <span id="session-selection-summary" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                Memuat sesi...
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 max-h-[380px] overflow-y-auto p-1 border border-slate-100 rounded-xl bg-slate-50/50" id="session-cards-container">
            @foreach ($sessions as $session)
                @php
                    $startTimeFormatted = substr((string) $session->start_time, 0, 5);
                    $endTimeFormatted = substr((string) $session->end_time, 0, 5);
                    $isSelected = in_array((int) $session->id, $selectedSessionIds, true);
                @endphp
                <label class="session-card relative flex items-center p-3 rounded-xl border cursor-pointer transition select-none
                    {{ $isSelected ? 'border-blue-600 bg-blue-50/60 ring-2 ring-blue-500/20' : 'border-slate-200 bg-white hover:bg-slate-50' }}">
                    <input type="checkbox" name="session_ids[]" value="{{ $session->id }}"
                        data-index="{{ $loop->index }}"
                        data-name="{{ $session->session_name }}"
                        data-start="{{ $startTimeFormatted }}"
                        data-end="{{ $endTimeFormatted }}"
                        {{ $isSelected ? 'checked' : '' }}
                        class="sr-only session-checkbox">
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
                        <div class="checkbox-indicator w-4 h-4 rounded border flex items-center justify-center shrink-0 ml-2
                            {{ $isSelected ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 bg-white text-transparent' }}">
                            <svg class="w-3 h-3 {{ $isSelected ? '' : 'hidden' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                </label>
            @endforeach
        </div>

        <p class="text-[11px] text-slate-500 mt-1.5">
            Klik satu atau beberapa kartu sesi sekaligus untuk meminjam lebih dari satu jam pelajaran dalam satu agenda.
        </p>

        <!-- Hidden inputs untuk jam mulai dan selesai agar sinkron dengan sistem -->
        <input type="hidden" id="start_time" name="start_time" value="{{ $defaultStart }}">
        <input type="hidden" id="end_time" name="end_time" value="{{ $defaultEnd }}">
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
            <li>Guru/pemohon dapat memilih <strong>lebih dari satu sesi</strong> dalam satu pengajuan.</li>
            <li>Peminjaman oleh Guru / Organisasi akan berstatus <strong>Pending</strong> menunggu verifikasi Waka Sarpras.</li>
            <li>Sistem secara otomatis memvalidasi bentrok jadwal di seluruh sesi yang dipilih.</li>
        </ul>
    </div>

    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
        <a href="{{ route('events.index') }}" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
            Batal
        </a>
        <button type="submit" id="submit-btn"
            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-2">
            <span>{{ $submitLabel ?? 'Kirim Pengajuan Agenda' }}</span>
        </button>
    </div>
</form>

<!-- Script Pengecekan Ketersediaan Real-Time Multi-Sesi via AJAX -->
<script>
    const roomSelect = document.getElementById('room_id');
    const dateInput = document.getElementById('date');
    const endDateInput = document.getElementById('end_date');
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const box = document.getElementById('availability-box');
    const icon = document.getElementById('availability-icon');
    const text = document.getElementById('availability-text');
    const summaryBadge = document.getElementById('session-selection-summary');
    const sessionCheckboxes = document.querySelectorAll('.session-checkbox');
    const sessionCards = document.querySelectorAll('.session-card');
    const submitBtn = document.getElementById('submit-btn');
    const excludeEventId = @json($event?->id);
    const isEditMode = Boolean(excludeEventId);

    dateInput.addEventListener('change', () => {
        if (dateInput.value) {
            endDateInput.min = dateInput.value;
            if (endDateInput.value && endDateInput.value < dateInput.value) {
                endDateInput.value = dateInput.value;
            }
        }
    });

    function getSelectedSessions() {
        return Array.from(document.querySelectorAll('.session-checkbox:checked'));
    }

    function updateCardStyles() {
        sessionCards.forEach(card => {
            const checkbox = card.querySelector('.session-checkbox');
            const indicator = card.querySelector('.checkbox-indicator');
            const checkIcon = indicator.querySelector('svg');

            if (checkbox.checked) {
                card.className = 'session-card relative flex items-center p-3 rounded-xl border cursor-pointer transition select-none border-blue-600 bg-blue-50/60 ring-2 ring-blue-500/20';
                indicator.className = 'checkbox-indicator w-4 h-4 rounded border flex items-center justify-center shrink-0 ml-2 border-blue-600 bg-blue-600 text-white';
                checkIcon.classList.remove('hidden');
            } else {
                card.className = 'session-card relative flex items-center p-3 rounded-xl border cursor-pointer transition select-none border-slate-200 bg-white hover:bg-slate-50';
                indicator.className = 'checkbox-indicator w-4 h-4 rounded border flex items-center justify-center shrink-0 ml-2 border-slate-300 bg-white text-transparent';
                checkIcon.classList.add('hidden');
            }
        });

        const selected = getSelectedSessions();
        if (selected.length === 0) {
            // Pada mode edit tanpa kecocokan sesi eksak, pertahankan jam manual hanya saat inisialisasi jika tidak pernah diubah user
            if (!isEditMode || window.__sessionTouched) {
                startTimeInput.value = '';
                endTimeInput.value = '';
            }
            summaryBadge.textContent = 'Belum ada sesi dipilih';
            summaryBadge.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200';
            return;
        }

        const first = selected[0];
        const last = selected[selected.length - 1];
        startTimeInput.value = first.dataset.start;
        endTimeInput.value = last.dataset.end;

        let hasGap = false;
        for (let i = 1; i < selected.length; i++) {
            const prevIdx = Number(selected[i - 1].dataset.index);
            const currIdx = Number(selected[i].dataset.index);
            if (currIdx !== prevIdx + 1) {
                hasGap = true;
                break;
            }
        }

        if (selected.length === 1) {
            summaryBadge.textContent = `1 Sesi (${first.dataset.start} - ${first.dataset.end} WIB)`;
            summaryBadge.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200';
        } else if (hasGap) {
            summaryBadge.textContent = `${selected.length} Sesi Dipilih (Terpisah)`;
            summaryBadge.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200';
        } else {
            summaryBadge.textContent = `${selected.length} Sesi Berurutan (${first.dataset.start} - ${last.dataset.end} WIB)`;
            summaryBadge.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200';
        }
    }

    async function cekKetersediaan() {
        const roomId = roomSelect.value;
        const date = dateInput.value;
        const endDate = endDateInput.value;
        const selected = getSelectedSessions();

        if (!roomId || !date || (selected.length === 0 && (!startTimeInput.value || !endTimeInput.value))) {
            box.classList.add('hidden');
            return;
        }

        const params = new URLSearchParams({
            room_id: roomId,
            date: date,
        });

        if (endDate) {
            params.append('end_date', endDate);
        }

        if (excludeEventId) {
            params.append('exclude_event_id', String(excludeEventId));
        }

        if (selected.length > 0) {
            selected.forEach(cb => params.append('session_ids[]', cb.value));
        } else {
            params.append('start_time', startTimeInput.value);
            params.append('end_time', endTimeInput.value);
        }

        try {
            const res = await fetch(`{{ route('events.check-availability') }}?${params.toString()}`);
            const data = await res.json();

            box.classList.remove('hidden');
            if (data.available) {
                box.className = 'p-3.5 rounded-xl border text-xs bg-emerald-50 text-emerald-800 border-emerald-200';
                icon.innerHTML = '<svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>';
                text.textContent = data.message || 'Jadwal Sesi Aman: Ruangan dan sesi terpilih tersedia untuk dipinjam.';
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

    sessionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            window.__sessionTouched = true;
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

