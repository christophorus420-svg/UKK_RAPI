@extends('layouts.app')

@section('title', 'Masuk ke Sistem')

@section('content')
<div class="min-h-[75vh] flex items-center justify-center py-6">
    <div class="w-full max-w-md">

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <!-- Header Card -->
            <div class="p-6 sm:p-8 bg-gradient-to-br from-blue-600 to-indigo-700 text-white text-center">
                <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mx-auto mb-3 p-2 shadow-md border border-white/20">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo SMK Negeri 1 Bantul" class="w-full h-full object-contain">
                </div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight">RAPI-Sekolah</h1>
                <p class="text-blue-100 text-xs sm:text-sm mt-1">
                    SMK Negeri 1 Bantul — Agenda & Sarpras Terpadu
                </p>
            </div>

            <!-- Form Body -->
            <div class="p-6 sm:p-8">
                @if (session('status'))
                    <div class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Peringatan Akses Perangkat -->
                <div class="mb-4 p-3 bg-blue-50/80 border border-blue-200 rounded-xl text-xs text-blue-800 flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <div class="leading-relaxed">
                        <strong class="font-semibold text-blue-900">Ketentuan Perangkat:</strong> Akses mobile (HP) diperuntukkan bagi <strong>PIC Ruangan, Guru, dan Siswa</strong>. Akun <strong>Waka / Admin</strong> wajib diakses melalui Komputer / Laptop.
                    </div>
                </div>

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="username" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                            Username
                        </label>
                        <div class="relative">
                            <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                                placeholder="Masukkan username">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                Kata Sandi
                            </label>
                            <a href="{{ route('password.request') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold transition">
                                Lupa password?
                            </a>
                        </div>
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                class="w-full pl-3.5 pr-11 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                                placeholder="Masukkan kata sandi">
                            <button type="button" onclick="togglePasswordVisibility('password', this)"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                                title="Lihat kata sandi" aria-label="Lihat kata sandi">
                                <svg class="eye-open-icon w-5 h-5 text-slate-400 hover:text-slate-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg class="eye-closed-icon w-5 h-5 text-blue-600 transition hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500">
                            <span>Ingat saya di perangkat ini</span>
                        </label>
                    </div>

                    <button type="submit"
                        class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-sm font-semibold transition shadow-sm hover:shadow flex items-center justify-center gap-2">
                        <span>Masuk ke Akun</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </form>

                <!-- Shortcut Akun Demo Penguji UKK -->
                <div class="mt-8 pt-6 border-t border-slate-100">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider text-center mb-3">
                        Pilih Akun Demo Ujian (Sekali Klik)
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <button type="button" onclick="isiAkun('admin', 'password123')"
                            class="px-2.5 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg font-medium border border-purple-200 text-left transition flex items-center justify-between">
                            <span>Waka / Admin</span>
                        </button>
                        <button type="button" onclick="isiAkun('pic_budi', 'password123')"
                            class="px-2.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg font-medium border border-amber-200 text-left transition flex items-center justify-between">
                            <span>PIC Ruang</span>
                        </button>
                        <button type="button" onclick="isiAkun('guru_andi', 'password123')"
                            class="px-2.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg font-medium border border-emerald-200 text-left transition flex items-center justify-between">
                            <span>Guru / Organisasi</span>
                        </button>
                        <button type="button" onclick="isiAkun('siswa_rizky', 'password123')"
                            class="px-2.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-medium border border-slate-200 text-left transition flex items-center justify-between">
                            <span>Siswa / Tamu</span>
                        </button>
                    </div>
                </div>

                <!-- Link Kalender Publik -->
                <div class="mt-6 text-center">
                    <a href="{{ route('calendar.public') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold hover:underline inline-flex items-center gap-1">
                        <span>Lihat Kalender Agenda Sekolah (Tanpa Login)</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function isiAkun(username, password) {
        document.getElementById('username').value = username;
        document.getElementById('password').value = password;
        document.getElementById('username').focus();
    }

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
