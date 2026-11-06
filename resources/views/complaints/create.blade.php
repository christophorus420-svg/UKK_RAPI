@extends('layouts.app')

@section('title', 'Lapor Kerusakan Sarpras')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
        <div class="mb-6">
            <a href="{{ route('complaints.index') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold inline-flex items-center gap-1 mb-2">
                &larr; Kembali ke Daftar Pengaduan
            </a>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 mb-2">
                <span>Laporan Bukti Fisik Real-Time</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Formulir Pengaduan Kerusakan Sarpras</h1>
            <p class="text-xs text-slate-500 mt-1">
                Unggah bukti foto fisik fasilitas yang rusak langsung dari kamera gawai Anda untuk ditindaklanjuti PIC ruangan.
            </p>
        </div>

        @if (isset($selectedEvent) && $selectedEvent)
            <div class="p-3.5 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900 flex items-start gap-2.5 mb-4 shadow-xs">
                <svg class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                <div>
                    <div class="font-bold">Laporan Cepat Terkait Peminjaman Ruang #{{ $selectedEvent->id }}</div>
                    <div class="text-[11px] text-amber-800 mt-0.5">
                        Kegiatan: <strong>{{ $selectedEvent->event_name }}</strong> • Ruangan: <strong>{{ $selectedEvent->room->room_name }}</strong>
                        • Tanggal: {{ \Carbon\Carbon::parse($selectedEvent->date)->translatedFormat('d M Y') }}
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('complaints.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            @if (isset($selectedEvent) && $selectedEvent)
                <input type="hidden" name="event_id" value="{{ $selectedEvent->id }}">
            @elseif (request('event_id'))
                <input type="hidden" name="event_id" value="{{ request('event_id') }}">
            @endif

            <!-- Pilihan Ruangan -->
            <div>
                <label for="room_id" class="block text-xs font-semibold text-slate-700 mb-1">
                    Pilih Ruangan Fasilitas Terkait <span class="text-rose-500">*</span>
                </label>
                <select id="room_id" name="room_id" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                    <option value="">-- Pilih Ruangan --</option>
                    @foreach ($rooms as $room)
                        @php
                            $targetRoomId = old('room_id', request('room_id', isset($selectedEvent) ? $selectedEvent->room_id : ''));
                        @endphp
                        <option value="{{ $room->id }}" {{ $targetRoomId == $room->id ? 'selected' : '' }}>
                            {{ $room->room_name }} (Lokasi: {{ $room->location }} • PIC: {{ $room->pic->fullname }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Deskripsi Kerusakan -->
            <div>
                <label for="description" class="block text-xs font-semibold text-slate-700 mb-1">
                    Deskripsi Detail Kerusakan Fisik <span class="text-rose-500">*</span>
                </label>
                @php
                    $defaultDescription = isset($selectedEvent) && $selectedEvent ? 'Kendala fasilitas saat pemakaian agenda "' . $selectedEvent->event_name . '": ' : '';
                @endphp
                <textarea id="description" name="description" rows="3" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                    placeholder="Contoh: Stopkontak nomor 4 di deret kiri mengeluarkan percikan api dan longgar, lampu proyektor padam total saat praktikum...">{{ old('description', $defaultDescription) }}</textarea>
                <p class="text-[11px] text-slate-400 mt-1">Minimal 10 karakter. Jelaskan lokasi fisik kerusakan secara spesifik.</p>
            </div>

            <!-- Unggah Foto Bukti Fisik Kamera Gawai (Real-Time) -->
            <div>
                <label for="photo" class="block text-xs font-semibold text-slate-700 mb-1">
                    Unggah Bukti Fisik (Kamera Smartphone / File)
                </label>
                <div class="mt-1 flex flex-col items-center justify-center border-2 border-dashed border-slate-300 rounded-2xl p-6 bg-slate-50/50 hover:bg-slate-50 transition">
                    <div id="preview-container" class="hidden mb-3 text-center">
                        <img id="image-preview" src="#" alt="Pratinjau Foto" class="max-h-52 rounded-xl object-cover shadow-sm mx-auto border border-slate-200">
                        <div id="compression-info" class="mt-2 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-lg border border-emerald-200 inline-block"></div>
                        <div class="mt-1">
                            <button type="button" onclick="hapusPreview()" class="text-[11px] text-rose-600 font-semibold hover:underline">
                                Hapus / Ambil Ulang Foto
                            </button>
                        </div>
                    </div>

                    <div id="upload-placeholder" class="text-center">
                        <svg class="mx-auto h-10 w-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <div class="mt-2 text-xs text-slate-600">
                            <label for="photo" class="cursor-pointer font-semibold text-blue-600 hover:text-blue-500">
                                <span>Buka Kamera atau Pilih Berkas</span>
                                <input type="file" id="photo" name="photo" accept="image/*" capture="environment" class="sr-only">
                            </label>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Mendukung JPEG, PNG, JPG, WebP hingga 5 MB (Otomatis terkompresi aman di server).</p>
                    </div>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('complaints.index') }}" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Batal
                </a>
                <button type="submit"
                    class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                    Kirim Laporan Kerusakan
                </button>
            </div>
        </form>
    </div>

</div>

<!-- Script Instant Preview & Otomatis Kompresi Foto di Sisi Klien -->
<script>
    const photoInput = document.getElementById('photo');
    const previewContainer = document.getElementById('preview-container');
    const imagePreview = document.getElementById('image-preview');
    const uploadPlaceholder = document.getElementById('upload-placeholder');
    const compressionInfo = document.getElementById('compression-info');

    photoInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        // Tampilkan placeholder loading / proses kompresi
        compressionInfo.textContent = 'Mengompresi foto...';
        previewContainer.classList.remove('hidden');
        uploadPlaceholder.classList.add('hidden');

        const reader = new FileReader();
        reader.onload = function (e) {
            const img = new Image();
            img.onload = function () {
                // Dimensi maksimal
                const maxDim = 1200;
                let width = img.width;
                let height = img.height;

                if (width > maxDim || height > maxDim) {
                    if (width >= height) {
                        height = Math.round((height / width) * maxDim);
                        width = maxDim;
                    } else {
                        width = Math.round((width / height) * maxDim);
                        height = maxDim;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                // Kompresi ke format JPEG kualitas 75%
                canvas.toBlob(function (blob) {
                    if (!blob) {
                        imagePreview.src = e.target.result;
                        compressionInfo.textContent = `Ukuran: ${(file.size / 1024).toFixed(0)} KB`;
                        return;
                    }

                    // Tampilkan pratinjau dari blob hasil kompresi
                    const compressedUrl = URL.createObjectURL(blob);
                    imagePreview.src = compressedUrl;

                    const originalKb = (file.size / 1024).toFixed(0);
                    const compressedKb = (blob.size / 1024).toFixed(0);
                    const savedPct = Math.max(0, Math.round((1 - blob.size / file.size) * 100));

                    compressionInfo.innerHTML = `Foto Terkompresi: <strong>${originalKb} KB</strong> &rarr; <strong class="text-emerald-900">${compressedKb} KB</strong> (Hemat ${savedPct}%)`;

                    // Ganti file di input dengan file terkompresi menggunakan DataTransfer
                    try {
                        const compressedFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(compressedFile);
                        photoInput.files = dataTransfer.files;
                    } catch (err) {
                        // Jika DataTransfer tidak didukung penuh, server-side compressor tetap menjamin ukuran kecil
                    }
                }, 'image/jpeg', 0.75);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });

    function hapusPreview() {
        photoInput.value = '';
        imagePreview.src = '#';
        compressionInfo.textContent = '';
        previewContainer.classList.add('hidden');
        uploadPlaceholder.classList.remove('hidden');
    }
</script>
@endsection
