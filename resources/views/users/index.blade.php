@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
<div class="space-y-6">

    <!-- Header & Action -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200 mb-2">
                <span>Otoritas & Manajemen Akun</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Kelola Pengguna Sistem</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Daftar akun Waka Sarpras, PIC Ruang, Guru Pengajar, dan Siswa/Tamu terdaftar.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="document.getElementById('modal-import-user').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold transition shadow-xs shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
                <span>Import Excel / CSV</span>
            </button>
            <a href="{{ route('users.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold transition shadow-xs shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                <span>Tambah Pengguna Baru</span>
            </a>
        </div>
    </div>

    <!-- Ringkasan Hasil Import Excel (Jika Baru Saja Melakukan Import) -->
    @if (session('import_result'))
        @php
            $res = session('import_result');
        @endphp
        <div class="bg-white rounded-2xl border-2 {{ $res['failed'] > 0 ? 'border-amber-300' : 'border-emerald-300' }} p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl {{ $res['failed'] > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }} flex items-center justify-center font-bold text-lg">
                        @if ($res['failed'] > 0)
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Ringkasan Hasil Import Pengguna</h3>
                        <p class="text-xs text-slate-500">Total {{ $res['total'] }} baris data diproses dari berkas.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-lg border border-emerald-200">
                        {{ $res['success'] }} Berhasil Ditambahkan
                    </span>
                    @if ($res['failed'] > 0)
                        <span class="px-3 py-1 bg-rose-100 text-rose-800 text-xs font-bold rounded-lg border border-rose-200">
                            {{ $res['failed'] }} Gagal / Dilewati
                        </span>
                    @endif
                </div>
            </div>

            @if (!empty($res['failures']))
                <div>
                    <h4 class="text-xs font-bold text-rose-800 mb-2 uppercase tracking-wider">Detail Baris yang Gagal / Dilewati:</h4>
                    <div class="overflow-x-auto border border-rose-100 rounded-xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-rose-50 text-rose-900 font-bold border-b border-rose-100">
                                <tr>
                                    <th class="p-2.5 w-16">Baris</th>
                                    <th class="p-2.5">Nama</th>
                                    <th class="p-2.5">Username</th>
                                    <th class="p-2.5">Alasan Kegagalan / Kendala Validasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-rose-50 bg-white">
                                @foreach ($res['failures'] as $fail)
                                    <tr class="hover:bg-rose-50/50">
                                        <td class="p-2.5 font-bold text-slate-700">#{{ $fail['row'] }}</td>
                                        <td class="p-2.5 font-medium text-slate-900">{{ $fail['fullname'] }}</td>
                                        <td class="p-2.5 font-mono text-slate-600">{{ $fail['username'] }}</td>
                                        <td class="p-2.5 text-rose-700 font-semibold">{{ $fail['reason'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Ringkasan Statistik Pengguna -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Akun</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $roleStats['total'] }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Pengguna terdaftar</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-semibold text-purple-600 uppercase tracking-wider">Waka / Admin</div>
            <div class="text-2xl font-bold text-purple-700 mt-1">{{ $roleStats['admin'] }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Akses penuh sistem</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-semibold text-amber-600 uppercase tracking-wider">PIC Ruangan</div>
            <div class="text-2xl font-bold text-amber-700 mt-1">{{ $roleStats['pic'] }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Pengelola sarpras</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-semibold text-emerald-600 uppercase tracking-wider">Guru / Organisasi</div>
            <div class="text-2xl font-bold text-emerald-700 mt-1">{{ $roleStats['guru'] }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Peminjam ruang</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs col-span-2 sm:col-span-1">
            <div class="text-[11px] font-semibold text-slate-600 uppercase tracking-wider">Siswa / Tamu</div>
            <div class="text-2xl font-bold text-slate-700 mt-1">{{ $roleStats['guest'] }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Akses terbatas</div>
        </div>
    </div>

    <!-- Filter Pencarian & Peran -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <div class="sm:col-span-6">
                <input type="text" name="q" value="{{ request('q') }}"
                    placeholder="Cari berdasarkan nama, username, email, atau nomor telepon..."
                    class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
            </div>
            <div class="sm:col-span-4">
                <select name="role_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white">
                    <option value="">Semua Peran (Role)</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r->id }}" {{ request('role_id') == $r->id ? 'selected' : '' }}>
                            {{ ucfirst($r->role_name) }}
                            @if ($r->role_name === 'admin') (Waka Sarpras)
                            @elseif ($r->role_name === 'pic') (Petugas PIC Ruang)
                            @elseif ($r->role_name === 'guru') (Guru / Organisasi)
                            @elseif ($r->role_name === 'guest') (Siswa / Tamu)
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2 flex gap-2">
                <button type="submit" class="flex-1 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold transition">
                    Filter
                </button>
                @if (request()->hasAny(['q', 'role_id']))
                    <a href="{{ route('users.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition flex items-center justify-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Pengguna -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase tracking-wider font-semibold text-[11px]">
                        <th class="py-3 px-4">Pengguna</th>
                        <th class="py-3 px-4">Kontak & Email</th>
                        <th class="py-3 px-4">Peran (Role)</th>
                        <th class="py-3 px-4">Keterkaitan Data</th>
                        <th class="py-3 px-4">Terdaftar</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $u)
                        @php
                            $roleName = $u->role?->role_name ?? 'guest';
                            $badgeClass = match($roleName) {
                                'admin' => 'bg-purple-100 text-purple-700 border-purple-200',
                                'pic' => 'bg-amber-100 text-amber-800 border-amber-200',
                                'guru' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                            $roleLabel = match($roleName) {
                                'admin' => 'Waka / Admin',
                                'pic' => 'PIC Ruang',
                                'guru' => 'Guru / Organisasi',
                                default => 'Siswa / Tamu',
                            };
                            $initials = collect(explode(' ', $u->fullname))
                                ->take(2)
                                ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                ->implode('');
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <!-- Kolom Pengguna -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-200 text-slate-700 font-bold flex items-center justify-center text-xs shrink-0 border border-slate-300">
                                        {{ $initials ?: 'U' }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                            <span>{{ $u->fullname }}</span>
                                            @if ($u->id === Auth::id())
                                                <span class="px-1.5 py-0.2 bg-blue-100 text-blue-700 rounded text-[10px] font-semibold border border-blue-200">Anda</span>
                                            @endif
                                        </div>
                                        <div class="text-slate-500 font-mono text-[11px]">@<span>{{ $u->username }}</span></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom Kontak -->
                            <td class="py-3.5 px-4">
                                <div class="space-y-0.5">
                                    <div class="text-slate-800 font-medium flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        <span>{{ $u->email ?: '-' }}</span>
                                    </div>
                                    <div class="text-slate-500 text-[11px] flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                        <span>{{ $u->phone_number ?: '-' }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom Peran -->
                            <td class="py-3.5 px-4">
                                <span class="inline-block px-2.5 py-0.5 rounded-full border text-[11px] font-semibold {{ $badgeClass }}">
                                    {{ $roleLabel }}
                                </span>
                            </td>

                            <!-- Kolom Keterkaitan Data -->
                            <td class="py-3.5 px-4 text-slate-600">
                                <div class="flex items-center gap-2 text-[11px]">
                                    @if ($u->rooms_count > 0)
                                        <span class="px-2 py-0.5 bg-amber-50 text-amber-700 rounded-md border border-amber-200 font-medium" title="Ruangan yang dipegang sebagai PIC">
                                            {{ $u->rooms_count }} Ruangan
                                        </span>
                                    @endif
                                    @if ($u->events_count > 0)
                                        <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md border border-blue-200 font-medium" title="Riwayat pengajuan agenda/peminjaman">
                                            {{ $u->events_count }} Agenda
                                        </span>
                                    @endif
                                    @if ($u->complaints_count > 0)
                                        <span class="px-2 py-0.5 bg-rose-50 text-rose-700 rounded-md border border-rose-200 font-medium" title="Laporan kerusakan sarpras yang diajukan">
                                            {{ $u->complaints_count }} Laporan
                                        </span>
                                    @endif
                                    @if ($u->rooms_count == 0 && $u->events_count == 0 && $u->complaints_count == 0)
                                        <span class="text-slate-400 italic">Belum ada riwayat</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Kolom Terdaftar -->
                            <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                {{ $u->created_at ? $u->created_at->format('d M Y') : '-' }}
                            </td>

                            <!-- Kolom Aksi -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('users.edit', $u) }}"
                                        class="p-1.5 text-slate-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                        title="Ubah data pengguna">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>

                                    @if ($u->id !== Auth::id())
                                        <form method="POST" action="{{ route('users.destroy', $u) }}" class="inline"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun pengguna {{ $u->fullname }} (@{{ $u->username }})? Tindakan ini tidak dapat dibatalkan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                                title="Hapus pengguna">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                Tidak ada data pengguna yang sesuai dengan kriteria pencarian Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>

<!-- MODAL IMPORT PENGGUNA EXCEL / CSV -->
<div id="modal-import-user" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 transform transition-all">
        <div class="flex items-start justify-between pb-3 border-b border-slate-100">
            <div>
                <div class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 uppercase mb-1 border border-emerald-200">
                    Import Data Massal
                </div>
                <h3 class="text-lg font-bold text-slate-900">Import Pengguna via Excel / CSV</h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Tambahkan banyak akun pengguna sekaligus secara otomatis.
                </p>
            </div>
            <button type="button" onclick="document.getElementById('modal-import-user').classList.add('hidden')"
                class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition" title="Tutup">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="mt-4 space-y-4">
            <!-- Unduh Template Panduan -->
            <div class="p-3.5 bg-blue-50/70 rounded-xl border border-blue-200 text-xs">
                <div class="font-bold text-blue-900 flex items-center justify-between">
                    <span>Langkah 1: Unduh Format Template</span>
                    <a href="{{ route('users.template-excel') }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition shadow-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        <span>Unduh Template Excel</span>
                    </a>
                </div>
                <p class="text-[11px] text-blue-800 mt-1.5 leading-relaxed">
                    Gunakan template resmi agar susunan kolom sesuai. Mendukung ekstensi <strong>.xlsx</strong>, <strong>.xls</strong>, dan <strong>.csv</strong>. Password opsional (default: <em>password123</em>).
                </p>
            </div>

            <!-- Form Upload Berkas -->
            <form method="POST" action="{{ route('users.import-excel') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Langkah 2: Pilih Berkas Hasil Pengisian <span class="text-rose-500">*</span>
                    </label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv,text/csv" required
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-200 file:text-slate-800 hover:file:bg-slate-300">
                    <p class="text-[10px] text-slate-400 mt-1">Maksimal 5 MB. Sistem akan memvalidasi format, memeriksa data kosong, dan mencegah username/email ganda.</p>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('modal-import-user').classList.add('hidden')"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-xs transition">
                        Mulai Import Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

