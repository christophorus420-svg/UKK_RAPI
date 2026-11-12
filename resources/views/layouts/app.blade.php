<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Beranda') - RAPI-Sekolah</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <!-- Tailwind CSS CDN & Font -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand Logo -->
                <div class="flex items-center gap-3">
                    <a href="{{ Auth::check() ? route('dashboard') : url('/') }}" class="flex items-center gap-2.5">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo SMK Negeri 1 Bantul" class="w-10 h-10 object-contain drop-shadow-sm">
                        <div>
                            <div class="font-bold text-slate-900 leading-tight">RAPI-Sekolah</div>
                            <div class="text-xs text-slate-500">SMK Negeri 1 Bantul</div>
                        </div>
                    </a>
                </div>

                <!-- Desktop Navigation Links -->
                @auth
                <nav class="hidden md:flex items-center gap-1">
                    <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('rooms.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('rooms.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        Ruangan
                    </a>
                    <a href="{{ route('events.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('events.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        Peminjaman & Agenda
                    </a>
                    <a href="{{ route('complaints.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('complaints.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        Pengaduan Sarpras
                    </a>
                    <a href="{{ route('calendar.public') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('calendar.public') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        Kalender Publik
                    </a>
                    @if (Auth::user()->isAdmin())
                    <a href="{{ route('users.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('users.*') ? 'bg-purple-50 text-purple-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        Pengguna
                    </a>
                    <a href="{{ route('reports.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('reports.*') ? 'bg-purple-50 text-purple-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        Rekap Laporan
                    </a>
                    @endif
                </nav>

                <!-- User Profile & Logout -->
                <div class="hidden md:flex items-center gap-3">
                    <div class="text-right">
                        <div class="text-sm font-semibold text-slate-900 leading-tight">{{ Auth::user()->fullname }}</div>
                        <div class="text-xs">
                            @php
                                $roleName = Auth::user()->role->role_name;
                                $badgeClass = match($roleName) {
                                    'admin' => 'bg-purple-100 text-purple-700 border-purple-200',
                                    'pic' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    'guru' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                                };
                                $roleLabel = match($roleName) {
                                    'admin' => 'Waka / Admin',
                                    'pic' => 'PIC Ruang',
                                    'guru' => 'Guru / Organisasi',
                                    default => 'Siswa / Tamu',
                                };
                            @endphp
                            <span class="inline-block px-2 py-0.5 rounded border text-[11px] font-semibold {{ $badgeClass }}">
                                {{ $roleLabel }}
                            </span>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Keluar dari sistem">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                        </button>
                    </form>
                </div>
                @else
                <div class="flex items-center gap-2">
                    <a href="{{ route('calendar.public') }}" class="px-3 py-1.5 text-sm font-medium text-slate-600 hover:text-slate-900">
                        Kalender Publik
                    </a>
                    <a href="{{ route('login') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition shadow-sm">
                        Masuk Sistem
                    </a>
                </div>
                @endauth

                <!-- Mobile Menu Button -->
                <div class="flex md:hidden items-center">
                    <button id="mobile-menu-button" type="button" class="p-2 rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        @auth
        <div id="mobile-menu" class="hidden md:hidden border-t border-slate-200 bg-white px-4 pt-2 pb-4 space-y-1">
            <div class="py-2 border-b border-slate-100 mb-2">
                <div class="font-semibold text-slate-900 text-sm">{{ Auth::user()->fullname }}</div>
                <div class="text-xs text-slate-500 capitalize">{{ Auth::user()->role->role_name }}</div>
            </div>
            <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                Dashboard
            </a>
            <a href="{{ route('rooms.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('rooms.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                Ruangan
            </a>
            <a href="{{ route('events.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('events.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                Peminjaman & Agenda
            </a>
            <a href="{{ route('complaints.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('complaints.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                Pengaduan Sarpras
            </a>
            <a href="{{ route('calendar.public') }}" class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('calendar.public') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                Kalender Publik
            </a>
            @if (Auth::user()->isAdmin())
            <a href="{{ route('users.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('users.*') ? 'bg-purple-50 text-purple-700 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                Kelola Pengguna
            </a>
            <a href="{{ route('reports.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('reports.*') ? 'bg-purple-50 text-purple-700 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                Rekap Laporan
            </a>
            @endif
            <div class="pt-2 border-t border-slate-100">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 rounded-lg">
                        Keluar dari Sistem
                    </button>
                </form>
            </div>
        </div>
        @endauth
    </header>

    <!-- Flash Messages Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 w-full">
        @if (session('success'))
            <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start gap-3 shadow-xs">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="flex-1">
                    <div class="font-medium">{{ session('success') }}</div>
                    @if (session('whatsapp_move_url'))
                        <div class="mt-2.5">
                            <x-whatsapp-button
                                :href="session('whatsapp_move_url')"
                                :label="'Kirim Peringatan Pindah Ruang ke ' . session('whatsapp_move_target') . ' via WhatsApp'"
                                title="Buka WhatsApp Web/Aplikasi dengan pesan resmi perpindahan ruangan" />
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start gap-3 shadow-xs">
                <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="font-medium">{{ session('error') }}</div>
            </div>
        @endif

        @if (session('info'))
            <div class="mb-4 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-sm flex items-start gap-3 shadow-xs">
                <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="font-medium">{{ session('info') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-xs">
                <div class="font-semibold mb-1">Harap periksa kembali input formulir Anda:</div>
                <ul class="list-disc list-inside text-xs space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
            <div>
                &copy; {{ date('Y') }} <strong>RAPI-Sekolah</strong> — Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak.
            </div>
            <div class="text-slate-400">
                Sistem Terpadu Agenda, Peminjaman Ruang & Pelaporan Fasilitas
            </div>
        </div>
    </footer>

    <!-- Mobile menu toggle script -->
    <script>
        const btn = document.getElementById('mobile-menu-button');
        const menu = document.getElementById('mobile-menu');
        if (btn && menu) {
            btn.addEventListener('click', () => {
                menu.classList.toggle('hidden');
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
