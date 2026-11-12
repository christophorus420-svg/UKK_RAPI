# RAPI-Sekolah (Rencana Agenda & Sarpras Terintegrasi)

**RAPI-Sekolah** adalah aplikasi web (Laravel 11) dan REST API terintegrasi untuk mengelola **jadwal peminjaman ruangan sekolah**, **pencegahan bentrok waktu otomatis**, serta **pelaporan dan pemeliharaan sarana prasarana (sarpras)** di SMK Negeri 1 Bantul. Proyek ini juga dilengkapi aplikasi pendamping mobile berbasis Flutter pada direktori [`rapi_sekolah_mobile/`](rapi_sekolah_mobile).

---

## Peran Pengguna & Hak Akses

Sistem menerapkan pembagian hak akses (*Role-Based Access Control*):

| Peran | Deskripsi Tugas & Hak Akses |
| :--- | :--- |
| **Waka Sarpras / Admin** (`admin`) | Mengelola master ruangan & delegasi PIC, menyetujui/menolak pengajuan agenda, menjalankan hak veto pindah ruangan, mengatur waktu sesi peminjaman, mengelola data pengguna & import massal, memantau eskalasi & peringatan WhatsApp, serta mengekspor rekap laporan PDF/CSV. *(Akses login dibatasi hanya dari perangkat desktop)*. |
| **PIC Ruangan** (`pic`) | Mengelola ruangan yang menjadi tanggung jawabnya (relasi *One-to-Many*), melihat jadwal agenda di ruangannya, dan menindaklanjuti laporan kerusakan fasilitas hingga berstatus selesai. |
| **Guru / Pembina Organisasi** (`guru`) | Mengajukan peminjaman ruangan (tunggal maupun multi-hari), memantau status pengajuan dan notifikasi pemindahan ruang, serta melaporkan kerusakan fasilitas beserta bukti foto. |
| **Siswa / Tamu / Publik** (`guest` / tanpa login) | Melihat jadwal kegiatan sekolah secara terbuka melalui halaman **Kalender Publik Sekolah**. |

---

## Fitur Utama Website

### 1. Kalender Publik Sekolah (`/kalender-sekolah`)
- Dapat diakses langsung oleh siswa, tamu, maupun publik tanpa perlu login.
- Menampilkan **tampilan kalender bulanan** dan **mode daftar agenda** khusus agenda resmi berstatus `approved`.
- Dilengkapi **legenda warna kepadatan agenda**, filter berdasarkan ruangan, serta pencarian agenda berdasarkan kata kunci.

### 2. Dashboard Khusus Berdasarkan Peran (`/dashboard`)
- **Dashboard Admin / Waka Sarpras:**
  - **Status "Ruang Kosong" & Ketersediaan Real-Time:** Memantau ruangan mana yang sedang kosong maupun yang **Sedang Digunakan** pada jam aktif saat ini.
  - **Eskalasi Kerusakan ke PIC via WhatsApp:** Menampilkan pengaduan berstatus *Belum Diperbaiki* yang melewati batas waktu (≥ 2 hari) beserta tombol **Peringatkan PIC via WhatsApp** (`https://wa.me/...`) dengan template pesan resmi otomatis.
  - **Peringatan Perpindahan Ruangan ke Guru via WhatsApp:** Menampilkan daftar agenda yang dipindahkan maupun agenda guru yang sedang/akan berlangsung lengkap dengan tombol **Peringatkan Guru via WhatsApp** dan **Peringatkan Pindah Ruang (WA)** yang langsung membuka pesan WA terformat ke nomor guru peminjam.
  - **Pengajuan Ruang Baru & Pengaturan Waktu Sesi:** Tinjau pengajuan pending secara cepat serta kelola jam operasional sesi peminjaman (Sesi 1 s.d. Sesi 13) beserta status aktif/nonaktifnya.
- **Dashboard PIC Ruangan:** Ringkasan ruangan tanggung jawab, aduan kerusakan masuk dengan pratinjau foto bukti, dan jadwal agenda terdekat di ruangan yang dikelola.
- **Dashboard Guru:** Daftar agenda pribadi, laporan kerusakan pribadi, serta **pop-up modal dan banner notifikasi dinamis** apabila ruangan kegiatannya dipindahkan oleh Waka Sarpras (lengkap dengan nama ruang pengganti dan alasan pemindahan).

### 3. Peminjaman Ruangan & Proteksi Anti-Bentrok (`/agenda`)
- **3 Kategori Jadwal Utama:** *Agenda Terkini (Sedang Berlangsung)*, *Agenda Hari Ini*, dan *Agenda Yang Akan Datang*.
- **Peminjaman Fleksibel:** Mendukung peminjaman 1 hari maupun **multi-hari** (`date` s.d. `end_date`) serta pemilihan rentang sesi waktu operasional.
- **Validasi Anti-Bentrok Mutlak:** Mengecek tumpang tindih tanggal, jam, dan ruangan secara real-time melalui endpoint `/api/cek-ketersediaan-ruang` dan validasi backend sebelum data disimpan.
- **Verifikasi & Penolakan Waka:** Pengajuan guru berstatus `pending` menunggu persetujuan Waka (penolakan wajib disertai alasan penolakan yang tampil ke pemohon).
- **Hak Veto Pemindahan Ruangan (`/agenda/{event}/pindah-ruang`):** Admin/Waka dapat memindahkan ruangan agenda yang sudah disetujui ke ruangan alternatif yang tersedia dengan alasan wajib, mencatat notifikasi untuk guru di dashboard, mengirim notifikasi via `WhatsAppService`, serta menyediakan tombol **Kirim Peringatan via WhatsApp**.
- **Status Pelaksanaan Dinamis:** Menghitung otomatis status *Pending*, *Ditolak*, *Belum Terlaksana*, *Sedang Berlangsung*, atau *Sudah Terlaksana* sesuai waktu berjalan.

### 4. Pengaduan Kerusakan Sarpras & Siklus Perbaikan (`/pengaduan`)
- Guru dan Admin dapat melaporkan kerusakan ruangan (bisa dikaitkan dengan agenda kegiatan tertentu) beserta **unggah foto bukti fisik** dan pratinjau modal foto.
- **Alur Status oleh PIC Ruangan Terkait:**
  - `Belum Diperbaiki` → `Menunggu Spare Part` → `Sudah Diperbaiki` (disertai catatan hasil perbaikan dan waktu selesai `repaired_at`).
  - Hanya PIC penanggung jawab ruangan terkait yang dapat mengubah status perbaikan.
  - Status **Sudah Diperbaiki** otomatis **terkunci** agar tidak dapat diubah kembali.
- **Pembersihan Otomatis 24 Jam:** Pengaduan yang telah berstatus *Sudah Diperbaiki* menampilkan hitung mundur dan dapat dibersihkan otomatis setelah 24 jam melalui command `php artisan complaints:purge-resolved`.
- **Eskalasi WhatsApp:** Admin dapat langsung memperingatkan PIC penanggung jawab ruangan lewat tombol **Peringatkan PIC via WhatsApp**.

### 5. Manajemen Ruangan, Pengguna & Rekap Laporan (Khusus Admin)
- **Master Data Ruangan (`/ruangan`):** Tambah, edit, aktif/nonaktifkan ketersediaan ruangan, dan delegasi PIC penanggung jawab. Ruangan yang memiliki riwayat agenda dilindungi dari penghapusan permanen.
- **Kelola Pengguna & Import Excel/CSV (`/pengguna`):** Manajemen akun pengguna (Admin, PIC, Guru, Guest), unduh **template Excel**, dan **import pengguna secara massal** dari file `.xlsx` atau `.csv`.
- **Rekap Laporan PDF & CSV (`/laporan`):** Rekap peminjaman ruangan dan pengaduan kerusakan berdasarkan periode harian/bulanan maupun per ruangan, lengkap dengan fitur ekspor ke **PDF** (landscape A4 via `barryvdh/laravel-dompdf`) dan **CSV**.

### 6. Keamanan, Autentikasi & Lupa Password
- Login menggunakan `username` dan `password` dengan fitur toggle tampilkan kata sandi.
- **Pembatasan Perangkat Admin (`RestrictAdminOnMobile`):** Akun dengan peran `admin` ditolak jika mencoba masuk melalui perangkat mobile (web mobile maupun API) agar pengelolaan administrasi tetap dilakukan melalui desktop.
- **Lupa Password (`/lupa-password`):** Mendukung pengiriman kode OTP pemulihan password melalui email dan verifikasi reset password (tersedia di web dan API).

---

## REST API & Aplikasi Mobile (`rapi_sekolah_mobile/`)

Aplikasi ini menyediakan REST API berformat JSON (`routes/api.php`) yang menggunakan autentikasi **Bearer Token** (`api_tokens`) untuk terhubung dengan aplikasi mobile Flutter di folder `rapi_sekolah_mobile/`:

- **Endpoint Publik:**
  - `POST /api/login`, `POST /api/forgot-password`, `POST /api/reset-password`
  - `GET /api/ruangan` & `GET /api/ruangan/{id}`
  - `GET /api/agenda` & `GET /api/agenda/{id}`
- **Endpoint Terproteksi (`Authorization: Bearer <token>`):**
  - `GET /api/me` / `GET /api/user`, `POST /api/logout`
  - `POST /api/agenda` (pengajuan agenda dengan validasi anti-bentrok)
  - `GET /api/pengaduan`, `POST /api/pengaduan` (dengan upload foto), `PATCH /api/pengaduan/{id}/status`, `DELETE /api/pengaduan/{id}`

---

## Teknologi yang Digunakan

- **Backend:** PHP 8.3+, Laravel Framework 11
- **Database:** MySQL / MariaDB
- **Frontend Web:** Laravel Blade, Tailwind CSS, JavaScript
- **PDF Export:** `barryvdh/laravel-dompdf`
- **Mobile App:** Flutter / Dart (`provider`, `dio`, `go_router`, `table_calendar`, `image_picker`)
- **Testing:** PHPUnit 12 (Feature & Unit Tests di folder `tests/`)

---

## Struktur Direktori Utama

```text
├── app/
│   ├── Http/Controllers/      # Controller Web & Api/ (REST API)
│   ├── Http/Middleware/       # RoleMiddleware, AuthenticateApiToken, RestrictAdminOnMobile
│   ├── Http/Requests/         # FormRequest validasi input & aturan bisnis
│   ├── Models/                # User, Role, Room, Event, Complaint, BookingSession, ApiToken
│   └── Services/              # WhatsAppService, UserImportService
├── database/
│   ├── migrations/            # Skema tabel & kolom fitur
│   └── seeders/               # DatabaseSeeder & RealisticRoomsSeeder
├── rapi_sekolah_mobile/       # Source code aplikasi mobile Flutter
├── resources/views/           # Tampilan Blade (dashboard, events, complaints, reports, dll.)
├── routes/
│   ├── web.php                # Rute aplikasi web
│   ├── api.php                # Rute REST API mobile
│   └── console.php            # Scheduler & command complaints:purge-resolved
└── tests/                     # Pengujian otomatis (Feature & Unit)
```

---

## Instalasi Lokal (Development)

1. **Clone repository dan masuk ke folder proyek:**
   ```bash
   git clone https://github.com/christophorus420-svg/UKK_RAPI.git
   cd UKK_RAPI
   ```

2. **Install dependensi & salin konfigurasi `.env`:**
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

3. **Konfigurasi database MySQL** pada file `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), lalu jalankan migrasi, seeder, dan storage link:
   ```bash
   php artisan migrate --seed
   php artisan storage:link
   ```

4. **Jalankan server lokal:**
   ```bash
   php artisan serve
   ```

5. **Jalankan pengujian otomatis:**
   ```bash
   php artisan test
   ```

### Akun Demo Default (Hasil `DatabaseSeeder`)

Semua akun demo di bawah menggunakan password **`password123`**:

| Peran | Username | Nama |
| :--- | :--- | :--- |
| **Admin / Waka Sarpras** | `admin` | Drs. H. Ahmad Fauzi, M.Pd (Waka Sarpras) |
| **PIC Ruangan** | `pic_budi` | Budi Santoso, S.Kom (Teknisi Lab) |
| **PIC Ruangan** | `pic_siti` | Siti Rahmawati, A.Md (Pengelola Gedung) |
| **Guru** | `guru_andi` | Andi Pratama, S.Pd (Guru Produktif RPL) |
| **Siswa / Tamu** | `siswa_rizky` | Rizky Ramadhan (Siswa RPL) |
