<!-- Modal Pratinjau Foto Kerusakan Fasilitas (Akses: Admin, PIC, dan Guru) -->
<div id="photoPreviewModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5">
    <div class="bg-white rounded-2xl max-w-3xl w-full overflow-hidden shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-200 flex flex-col max-h-[92vh]">
        <!-- Header Modal -->
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-sm sm:text-base leading-tight">Bukti Foto Kerusakan Fasilitas</h3>
                    <p id="modalPhotoRoomInfo" class="text-xs text-slate-500 mt-0.5"></p>
                </div>
            </div>
            <button type="button" onclick="closePhotoModal()" class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-200 rounded-xl transition" title="Tutup (ESC)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Body Modal: Gambar Resolusi Penuh -->
        <div class="flex-1 overflow-auto bg-slate-950 flex items-center justify-center p-3 sm:p-5 min-h-[240px] max-h-[58vh]">
            <img id="modalPhotoImage" src="" alt="Bukti Foto Kerusakan" class="max-h-full max-w-full object-contain rounded-lg shadow-xl"
                onerror="this.onerror=null; this.src='{{ asset('images/placeholder-no-image.svg') }}';">
        </div>

        <!-- Footer Modal: Keterangan & Detail Laporan -->
        <div class="p-4 sm:p-5 bg-white border-t border-slate-100 text-xs space-y-3 shrink-0">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span id="modalPhotoStatusBadge" class="px-2.5 py-1 rounded-lg font-bold text-xs border"></span>
                    <span id="modalPhotoTime" class="text-slate-400 text-[11px]"></span>
                </div>
                <div class="flex items-center gap-2">
                    <a id="modalPhotoDownloadBtn" href="" target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition text-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        <span>Buka Tab Baru</span>
                    </a>
                    <button type="button" onclick="closePhotoModal()"
                        class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-semibold transition text-xs">
                        Tutup
                    </button>
                </div>
            </div>

            <!-- Detail Deskripsi & Penanggung Jawab -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1.5">
                <div class="text-slate-700 leading-relaxed">
                    <span class="font-bold text-slate-900 block text-[11px] uppercase tracking-wider text-slate-500 mb-0.5">Deskripsi Kerusakan:</span>
                    <span id="modalPhotoDescription" class="italic"></span>
                </div>
                <div class="pt-1.5 border-t border-slate-200/60 flex flex-wrap items-center justify-between text-[11px] text-slate-500 gap-2">
                    <span>Pelapor: <strong id="modalPhotoReporter" class="text-slate-700"></strong></span>
                    <span>PIC Ruangan: <strong id="modalPhotoPic" class="text-slate-700"></strong></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openPhotoModal(data) {
        document.getElementById('modalPhotoImage').src = data.url;
        document.getElementById('modalPhotoRoomInfo').textContent = data.room + (data.location ? ' • ' + data.location : '');
        document.getElementById('modalPhotoDescription').textContent = '"' + data.description + '"';
        document.getElementById('modalPhotoReporter').textContent = data.reporter;
        document.getElementById('modalPhotoPic').textContent = data.pic;
        document.getElementById('modalPhotoTime').textContent = data.time || '';
        document.getElementById('modalPhotoDownloadBtn').href = data.url;

        const badge = document.getElementById('modalPhotoStatusBadge');
        badge.textContent = data.status || 'Belum Diperbaiki';
        badge.className = 'px-2.5 py-1 rounded-lg font-bold text-xs border ' + (data.statusClass || 'bg-rose-100 text-rose-700 border-rose-200');

        const modal = document.getElementById('photoPreviewModal');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closePhotoModal() {
        const modal = document.getElementById('photoPreviewModal');
        if (modal) {
            modal.classList.add('hidden');
            document.getElementById('modalPhotoImage').src = '';
            document.body.style.overflow = '';
        }
    }

    // Tutup modal jika tombol ESC ditekan
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePhotoModal();
        }
    });

    // Tutup jika klik area latar belakang (backdrop)
    document.getElementById('photoPreviewModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closePhotoModal();
        }
    });
</script>
