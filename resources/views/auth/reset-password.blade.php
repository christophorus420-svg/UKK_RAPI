@extends('layouts.app')

@section('title', 'Atur Ulang Kata Sandi - RAPI-Sekolah')

@section('content')
<div class="min-h-[75vh] flex items-center justify-center py-6 px-4">
    <div class="max-w-md w-full">

        <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
            <!-- Header Card -->
            <div class="bg-gradient-to-r from-blue-700 to-indigo-800 p-6 sm:p-8 text-white text-center">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-200 border border-emerald-400/30 mb-3">
                    <span>Verifikasi Email Terdaftar</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight">Atur Kata Sandi Baru</h1>
                <p class="text-blue-100 text-xs sm:text-sm mt-1 max-w-sm mx-auto">
                    Masukkan kode OTP yang dikirimkan ke email akun <strong class="text-white">{{ $username }}</strong>.
                </p>
            </div>

            <!-- Form Body -->
            <div class="p-6 sm:p-8">

                <!-- Alert Error / Sukses -->
                @if ($errors->any())
                    <div class="mb-5 p-3.5 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-xs space-y-1">
                        @foreach ($errors->all() as $error)
                            <div class="font-medium">• {{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-5 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-5 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="username" value="{{ $username }}">
                    <input type="hidden" name="email" value="{{ $email ?? '' }}">

                    <!-- Field Input Kode OTP -->
                    <div>
                        <label for="otp" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                            Kode Verifikasi OTP (6 Digit) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" id="otp" name="otp" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                                value="{{ old('otp', $prefilledOtp ?? '') }}" required autofocus
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-center text-xl font-mono font-bold tracking-[0.25em] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition text-blue-900"
                                placeholder="------">
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Masukkan 6 digit angka kode OTP yang dikirimkan ke email akun Anda.
                        </p>
                    </div>

                    <!-- Field Password Baru -->
                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                            Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                class="w-full pl-3.5 pr-11 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                                placeholder="Minimal 8 karakter">
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

                    <!-- Field Konfirmasi Password Baru -->
                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                            Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" id="password_confirmation" name="password_confirmation" required
                                class="w-full pl-3.5 pr-11 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                                placeholder="Ulangi kata sandi baru">
                            <button type="button" onclick="togglePasswordVisibility('password_confirmation', this)"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                                title="Lihat konfirmasi kata sandi" aria-label="Lihat konfirmasi kata sandi">
                                <svg class="eye-open-icon w-5 h-5 text-slate-400 hover:text-slate-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg class="eye-closed-icon w-5 h-5 text-blue-600 transition hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Pastikan kata sandi baru minimal 8 karakter dan mudah Anda ingat.
                        </p>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-sm font-semibold transition shadow-sm hover:shadow flex items-center justify-center gap-2">
                            <span>Verifikasi OTP & Simpan Kata Sandi</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Batal & Kembali -->
                <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                    <a href="{{ route('login') }}" class="text-xs text-slate-600 hover:text-blue-600 font-semibold inline-flex items-center gap-1.5 transition">
                        <span>Batal & Kembali ke Halaman Masuk</span>
                    </a>
                </div>

            </div>
        </div>

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
