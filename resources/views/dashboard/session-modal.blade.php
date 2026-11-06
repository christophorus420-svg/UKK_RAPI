<!-- Modal Edit Waktu Sesi Peminjaman Ruang (Akses: Waka / Admin) -->
<div id="modal-edit-sesi" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 animate-in fade-in duration-200 flex flex-col max-h-[92vh]">
        <!-- Modal Header -->
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 id="modalSessionTitle" class="font-bold text-slate-900 text-base leading-tight">Edit Waktu Sesi Peminjaman</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Atur jam mulai dan jam selesai untuk sesi peminjaman ruang</p>
                </div>
            </div>
            <button type="button" onclick="closeEditSessionModal()" class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-200 rounded-xl transition cursor-pointer" title="Tutup (ESC)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Form Edit Sesi -->
        <form id="form-edit-sesi" method="POST" action="" class="p-6 space-y-4 overflow-y-auto">
            @csrf
            @method('PUT')

            <div>
                <label for="edit-session-name" class="block text-xs font-semibold text-slate-700 mb-1">
                    Nama / Label Sesi <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="edit-session-name" name="session_name" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                    placeholder="Contoh: Sesi 1 (Pagi Awal)">
                <p class="text-[11px] text-slate-400 mt-1">Nama sesi yang ditampilkan pada pilihan peminjaman guru.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="edit-start-time" class="block text-xs font-semibold text-slate-700 mb-1">
                        Jam Mulai <span class="text-rose-500">*</span>
                    </label>
                    <input type="time" id="edit-start-time" name="start_time" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                </div>
                <div>
                    <label for="edit-end-time" class="block text-xs font-semibold text-slate-700 mb-1">
                        Jam Selesai <span class="text-rose-500">*</span>
                    </label>
                    <input type="time" id="edit-end-time" name="end_time" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                </div>
            </div>

            <!-- Checkbox Status Aktif -->
            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                <label class="flex items-start gap-2.5 cursor-pointer select-none">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="edit-is-active" name="is_active" value="1"
                        class="w-4 h-4 mt-0.5 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                    <div>
                        <span class="text-xs font-semibold text-slate-800">Aktifkan Sesi Ini</span>
                        <p class="text-[11px] text-slate-500 mt-0.5">Sesi nonaktif tidak akan dapat dipilih saat guru/organisasi mengajukan peminjaman ruang baru.</p>
                    </div>
                </label>
            </div>

            <div class="p-3.5 bg-blue-50/70 border border-blue-200 rounded-xl text-xs text-blue-800 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Perubahan waktu sesi akan langsung mempengaruhi pengecekan bentrok jadwal otomatis pada sistem peminjaman ruangan sekolah.</span>
            </div>

            <!-- Modal Actions -->
            <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeEditSessionModal()"
                    class="px-4 py-2 border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold transition cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                    class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Simpan Perubahan Waktu</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditSessionModal(data) {
        document.getElementById('form-edit-sesi').action = data.actionUrl;
        document.getElementById('edit-session-name').value = data.name;
        document.getElementById('edit-start-time').value = data.start;
        document.getElementById('edit-end-time').value = data.end;
        document.getElementById('edit-is-active').checked = !!data.active;
        document.getElementById('modalSessionTitle').textContent = 'Edit ' + data.name;

        const modal = document.getElementById('modal-edit-sesi');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeEditSessionModal() {
        const modal = document.getElementById('modal-edit-sesi');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    // Tutup modal jika tombol ESC ditekan
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEditSessionModal();
        }
    });

    // Tutup jika klik area latar belakang (backdrop)
    document.getElementById('modal-edit-sesi')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditSessionModal();
        }
    });
</script>
