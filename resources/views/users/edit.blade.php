@extends('layouts.app')

@section('title', 'Ubah Data Pengguna')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
        <div class="mb-6">
            <a href="{{ route('users.index') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold inline-flex items-center gap-1 mb-2">
                &larr; Kembali ke Daftar Pengguna
            </a>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Ubah Data Pengguna</h1>
            <p class="text-xs text-slate-500 mt-1">
                Perbarui identitas, kontak, atau hak akses akun <strong class="text-slate-800">{{ $user->fullname }}</strong>.
            </p>
        </div>

        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- Nama Lengkap -->
            <div>
                <label for="fullname" class="block text-xs font-semibold text-slate-700 mb-1">
                    Nama Lengkap & Gelar <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="fullname" name="fullname" value="{{ old('fullname', $user->fullname) }}" required autofocus
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
            </div>

            <!-- Grid Username & Email -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="username" class="block text-xs font-semibold text-slate-700 mb-1">
                        Username <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                    <p class="text-[11px] text-slate-400 mt-1">Digunakan untuk login ke sistem.</p>
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">
                        Alamat Email <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                    <p class="text-[11px] text-slate-400 mt-1">Tujuan pengiriman kode OTP reset kata sandi.</p>
                </div>
            </div>

            <!-- Grid Nomor Telepon & Peran -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="phone_number" class="block text-xs font-semibold text-slate-700 mb-1">
                        Nomor Telepon / WhatsApp <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                        placeholder="Contoh: 081234567890">
                </div>

                <div>
                    <label for="role_id" class="block text-xs font-semibold text-slate-700 mb-1">
                        Peran & Hak Akses (Role) <span class="text-rose-500">*</span>
                    </label>
                    <select id="role_id" name="role_id" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                @if ($role->role_name === 'admin')
                                    Waka Sarpras / Admin (Kelola seluruh sistem & ruangan)
                                @elseif ($role->role_name === 'pic')
                                    PIC Ruang (Penanggung jawab ruangan & perbaikan sarpras)
                                @elseif ($role->role_name === 'guru')
                                    Guru / Organisasi (Dapat mengajukan peminjaman ruangan)
                                @elseif ($role->role_name === 'guest')
                                    Siswa / Tamu (Akses kalender publik & pelaporan umum)
                                @else
                                    {{ ucfirst($role->role_name) }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Bagian Ubah Password (Opsional) -->
            <div class="pt-4 border-t border-slate-100">
                <div class="mb-3">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Ubah Kata Sandi (Opsional)</h3>
                    <p class="text-[11px] text-slate-500">Biarkan kedua kolom di bawah kosong jika tidak ingin mengubah kata sandi pengguna ini.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">
                            Kata Sandi Baru
                        </label>
                        <div class="relative">
                            <input type="password" id="password" name="password"
                                class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                                placeholder="Kosongkan jika tidak diubah">
                            <button type="button" onclick="togglePasswordVisibility('password', this)"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                                title="Lihat kata sandi">
                                <svg class="eye-open-icon w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg class="eye-closed-icon w-4 h-4 text-blue-600 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">
                            Konfirmasi Kata Sandi Baru
                        </label>
                        <div class="relative">
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                                placeholder="Ulangi kata sandi baru">
                            <button type="button" onclick="togglePasswordVisibility('password_confirmation', this)"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                                title="Lihat konfirmasi kata sandi">
                                <svg class="eye-open-icon w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg class="eye-closed-icon w-4 h-4 text-blue-600 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('users.index') }}" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function togglePasswordVisibility(inputId, btnEl) {
        const input = document.getElementById(inputId);
        if (!input) return;

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        const eyeOpen = btnEl.querySelector('.eye-open-icon');
        const eyeClosed = btnEl.querySelector('.eye-closed-icon');

        if (eyeOpen && eyeClosed) {
            if (isPassword) {
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
                btnEl.setAttribute('aria-label', 'Sembunyikan kata sandi');
                btnEl.setAttribute('title', 'Sembunyikan kata sandi');
            } else {
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
                btnEl.setAttribute('aria-label', 'Lihat kata sandi');
                btnEl.setAttribute('title', 'Lihat kata sandi');
            }
        }
    }
</script>
@endsection

