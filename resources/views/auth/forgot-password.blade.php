@extends('layouts.app')

@section('title', 'Lupa Kata Sandi - RAPI-Sekolah')

@section('content')
<div class="min-h-[75vh] flex items-center justify-center py-6 px-4">
    <div class="max-w-md w-full">

        <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
            <!-- Header Card -->
            <div class="bg-gradient-to-r from-blue-700 to-indigo-800 p-6 sm:p-8 text-white text-center">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/15 text-blue-100 border border-white/20 mb-3">
                    <span>Pemulihan Akun Terdaftar</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight">Lupa Kata Sandi?</h1>
                <p class="text-blue-100 text-xs sm:text-sm mt-1 max-w-sm mx-auto">
                    Masukkan username atau alamat email terdaftar untuk menerima kode verifikasi OTP melalui email Anda.
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
                    <div class="mb-5 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs space-y-2">
                        <div class="font-bold flex items-center gap-1.5 text-sm text-emerald-900">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Kode OTP Telah Dikirim ke Email!</span>
                        </div>
                        <p>{{ session('status') }}</p>
                        @if (session('reset_link'))
                            <div class="pt-1">
                                <a href="{{ session('reset_link') }}"
                                    class="w-full inline-flex items-center justify-center gap-2 py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs transition">
                                    <span>Lanjut ke Form Reset Password Baru &rarr;</span>
                                </a>
                            </div>
                        @endif
                    </div>
                @endif

                <form method="POST" action="{{ route('password.send-token') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                            Alamat Email atau Username Terdaftar <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="email" name="email" value="{{ old('email', old('username')) }}" required autofocus
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                            placeholder="Contoh: phorus_admin atau christophorus420@gmail.com">
                        <p class="text-[11px] text-slate-500 mt-1">
                            Kode OTP 6 digit akan dikirimkan ke alamat email yang terdaftar pada akun ini.
                        </p>
                    </div>

                    <div>
                        <label for="phone_number" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                            Nomor Telepon Terdaftar / WhatsApp <span class="text-slate-400 font-normal">(Opsional)</span>
                        </label>
                        <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number') }}"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                            placeholder="Contoh: 081234567890">
                        <p class="text-[11px] text-slate-500 mt-1">
                            Verifikasi tambahan nomor telepon untuk proteksi keamanan identitas akun.
                        </p>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-sm font-semibold transition shadow-sm hover:shadow flex items-center justify-center gap-2">
                            <span>Kirim Kode OTP ke Email</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Kembali ke Login -->
                <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                    <a href="{{ route('login') }}" class="text-xs text-slate-600 hover:text-blue-600 font-semibold inline-flex items-center gap-1.5 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span>Kembali ke Halaman Masuk</span>
                    </a>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection
