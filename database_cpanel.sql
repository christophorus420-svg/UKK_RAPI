-- RAPI Sekolah MySQL Database Dump for cPanel
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+07:00';

-- Hapus tabel lama jika ada (urutan dari tabel relasi terluar)
DROP TABLE IF EXISTS `complaints`, `events`, `booking_sessions`, `rooms`, `api_tokens`, `users`, `roles`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `failed_jobs`, `job_batches`, `jobs`, `migrations`;

-- --------------------------------------------------------
-- Table structure for `roles`
-- --------------------------------------------------------
CREATE TABLE `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `roles`
INSERT INTO `roles` (`id`, `role_name`, `created_at`, `updated_at`) VALUES
('1', 'admin', '2026-11-06 08:55:36', '2026-11-06 08:55:36'),
('2', 'pic', '2026-11-06 08:55:36', '2026-11-06 08:55:36'),
('3', 'guru', '2026-11-06 08:55:36', '2026-11-06 08:55:36'),
('4', 'guest', '2026-11-06 08:55:36', '2026-11-06 08:55:36');

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `fullname` VARCHAR(255) NOT NULL,
  `phone_number` VARCHAR(255) NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `remember_token` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `email` VARCHAR(255) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `users`
INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `phone_number`, `role_id`, `remember_token`, `created_at`, `updated_at`, `email`) VALUES
('1', 'admin', '$2y$12$M1Z6r2Tr7fWl4SgPXvmFE.e/xhgZgAk7MArZvhVVxrHlpaEfvNW/O', 'Drs. H. Ahmad Fauzi, M.Pd (Waka Sarpras)', '6281234567890', '1', NULL, '2026-11-06 08:55:37', '2026-11-06 08:55:37', NULL),
('2', 'phorus_admin', '$2y$12$dCRt1wL3L9SpCS2u4YVjRelQfftQZiEgFbXbJvUYX597rbZ.xe1W6', 'Phorus Admin', '081234567890', '1', NULL, '2026-11-06 08:55:37', '2026-11-06 08:55:37', 'christophorus420@gmail.com'),
('3', 'pic_budi', '$2y$12$M1Z6r2Tr7fWl4SgPXvmFE.e/xhgZgAk7MArZvhVVxrHlpaEfvNW/O', 'Budi Santoso, S.Kom (Teknisi Lab)', '6285712345678', '2', NULL, '2026-11-06 08:55:37', '2026-11-06 08:55:37', NULL),
('4', 'pic_siti', '$2y$12$M1Z6r2Tr7fWl4SgPXvmFE.e/xhgZgAk7MArZvhVVxrHlpaEfvNW/O', 'Siti Rahmawati, A.Md (Pengelola Gedung)', '6281987654321', '2', NULL, '2026-11-06 08:55:37', '2026-11-06 08:55:37', NULL),
('5', 'guru_andi', '$2y$12$M1Z6r2Tr7fWl4SgPXvmFE.e/xhgZgAk7MArZvhVVxrHlpaEfvNW/O', 'Andi Pratama, S.Pd (Guru Produktif RPL)', '6282133445566', '3', NULL, '2026-11-06 08:55:37', '2026-11-06 08:55:37', NULL),
('6', 'osis_smk', '$2y$12$M1Z6r2Tr7fWl4SgPXvmFE.e/xhgZgAk7MArZvhVVxrHlpaEfvNW/O', 'Pengurus OSIS SMK', '6282299887766', '3', NULL, '2026-11-06 08:55:37', '2026-11-06 08:55:37', NULL),
('7', 'siswa_rizky', '$2y$12$M1Z6r2Tr7fWl4SgPXvmFE.e/xhgZgAk7MArZvhVVxrHlpaEfvNW/O', 'Rizky Ramadhan (Siswa RPL)', '6281355667788', '4', NULL, '2026-11-06 08:55:37', '2026-11-06 08:55:37', NULL);

-- --------------------------------------------------------
-- Table structure for `password_reset_tokens`
-- --------------------------------------------------------
CREATE TABLE `password_reset_tokens` (
  `username` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NULL,
  `email` VARCHAR(255) NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `sessions`
-- --------------------------------------------------------
CREATE TABLE `sessions` (
  `id` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(255) NULL,
  `user_agent` TEXT NULL,
  `payload` TEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `cache`
-- --------------------------------------------------------
CREATE TABLE `cache` (
  `key` VARCHAR(255) NOT NULL,
  `value` TEXT NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `cache_locks`
-- --------------------------------------------------------
CREATE TABLE `cache_locks` (
  `key` VARCHAR(255) NOT NULL,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `jobs`
-- --------------------------------------------------------
CREATE TABLE `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` VARCHAR(255) NOT NULL,
  `payload` TEXT NOT NULL,
  `attempts` INT NOT NULL,
  `reserved_at` INT NULL,
  `available_at` INT NOT NULL,
  `created_at` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `job_batches`
-- --------------------------------------------------------
CREATE TABLE `job_batches` (
  `id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` TEXT NOT NULL,
  `options` TEXT NULL,
  `cancelled_at` INT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `failed_jobs`
-- --------------------------------------------------------
CREATE TABLE `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(255) NOT NULL,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` TEXT NOT NULL,
  `exception` TEXT NOT NULL,
  `failed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `rooms`
-- --------------------------------------------------------
CREATE TABLE `rooms` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_name` VARCHAR(255) NOT NULL,
  `capacity` INT NOT NULL DEFAULT '0',
  `location` VARCHAR(255) NOT NULL,
  `pic_id` BIGINT UNSIGNED NOT NULL,
  `is_active` INT NOT NULL DEFAULT '1',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `rooms`
INSERT INTO `rooms` (`id`, `room_name`, `capacity`, `location`, `pic_id`, `is_active`, `created_at`, `updated_at`) VALUES
('1', 'Lab Komputer RPL 1', '36', 'Gedung B Lantai 2', '3', '1', '2026-11-06 08:55:37', '2026-11-06 08:55:37'),
('2', 'Lab Komputer RPL 2', '36', 'Gedung B Lantai 2', '3', '1', '2026-11-06 08:55:37', '2026-11-06 08:55:37'),
('3', 'Aula Graha Wiyata', '300', 'Gedung Utama Lantai 1', '4', '1', '2026-11-06 08:55:37', '2026-11-06 08:55:37'),
('4', 'Ruang Rapat Guru', '40', 'Gedung A Lantai 1', '4', '1', '2026-11-06 08:55:37', '2026-11-06 08:55:37'),
('5', 'Bengkel Kreatif & IoT', '30', 'Gedung C Lantai 1', '3', '1', '2026-11-06 08:55:37', '2026-11-06 08:55:37'),
('6', 'Ruang Kelas X RPL 1', '36', 'Gedung D Lantai 1', '4', '1', '2026-11-06 08:55:37', '2026-11-06 08:55:37'),
('7', 'Ruang Kelas XI RPL 1', '36', 'Gedung D Lantai 2', '4', '1', '2026-11-06 08:55:37', '2026-11-06 08:55:37'),
('8', 'Ruang Kelas XII RPL 1', '36', 'Gedung D Lantai 3', '4', '1', '2026-11-06 08:55:37', '2026-11-06 08:55:37'),
('9', 'Perpustakaan Utama (Ruang Baca & Referensi)', '60', 'Gedung A Lantai 2', '4', '1', '2026-11-06 08:55:37', '2026-11-06 08:55:37'),
('10', 'Laboratorium Bahasa & Multimedia', '40', 'Gedung B Lantai 1', '3', '1', '2026-11-06 08:55:37', '2026-11-06 08:55:37'),
('11', 'Laboratorium IPA Terapan', '36', 'Gedung C Lantai 2', '3', '1', '2026-11-06 08:55:37', '2026-11-06 08:55:37'),
('12', 'Ruang Konseling & Bimbingan Karir (BK)', '15', 'Gedung A Lantai 1', '4', '1', '2026-11-06 08:55:37', '2026-11-06 08:55:37');

-- --------------------------------------------------------
-- Table structure for `booking_sessions`
-- --------------------------------------------------------
CREATE TABLE `booking_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_name` VARCHAR(255) NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `is_active` INT NOT NULL DEFAULT '1',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `booking_sessions`
INSERT INTO `booking_sessions` (`id`, `session_name`, `start_time`, `end_time`, `is_active`, `created_at`, `updated_at`) VALUES
('1', 'Sesi 1 (Jam Ke-1)', '07:00:00', '07:45:00', '1', '2026-11-06 08:55:21', '2026-11-06 08:55:21'),
('2', 'Sesi 2 (Jam Ke-2)', '07:45:00', '08:30:00', '1', '2026-11-06 08:55:21', '2026-11-06 08:55:21'),
('3', 'Sesi 3 (Jam Ke-3)', '08:30:00', '09:15:00', '1', '2026-11-06 08:55:21', '2026-11-06 08:55:21'),
('4', 'Sesi 4 (Jam Ke-4)', '09:15:00', '10:00:00', '1', '2026-11-06 08:55:21', '2026-11-06 08:55:21'),
('5', 'Sesi 5 (Jam Ke-5)', '10:00:00', '10:45:00', '1', '2026-11-06 08:55:21', '2026-11-06 08:55:21'),
('6', 'Sesi 6 (Jam Ke-6)', '10:45:00', '11:30:00', '1', '2026-11-06 08:55:21', '2026-11-06 08:55:21'),
('7', 'Sesi 7 (Jam Ke-7)', '11:30:00', '12:15:00', '1', '2026-11-06 08:55:21', '2026-11-06 08:55:21'),
('8', 'Sesi 8 (Jam Ke-8)', '12:15:00', '13:00:00', '1', '2026-11-06 08:55:21', '2026-11-06 08:55:21'),
('9', 'Sesi 9 (Jam Ke-9)', '13:00:00', '13:45:00', '1', '2026-11-06 08:55:21', '2026-11-06 08:55:21'),
('10', 'Sesi 10 (Jam Ke-10)', '13:45:00', '14:30:00', '1', '2026-11-06 08:55:21', '2026-11-06 08:55:21'),
('11', 'Sesi 11 (Jam Ke-11)', '14:30:00', '15:15:00', '1', '2026-11-06 08:55:21', '2026-11-06 08:55:21'),
('12', 'Sesi 12 (Jam Ke-12)', '15:15:00', '16:00:00', '1', '2026-11-06 08:55:21', '2026-11-06 08:55:21'),
('13', 'Sesi 13 (Jam Ke-13)', '16:00:00', '16:45:00', '1', '2026-11-06 08:55:21', '2026-11-06 08:55:21');

-- --------------------------------------------------------
-- Table structure for `events`
-- --------------------------------------------------------
CREATE TABLE `events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id` BIGINT UNSIGNED NOT NULL,
  `pic_id` BIGINT UNSIGNED NOT NULL,
  `event_name` VARCHAR(255) NOT NULL,
  `date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'pending',
  `approved_by` INT NULL,
  `move_reason` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `alasan_penolakan` TEXT NULL,
  `session_id` BIGINT UNSIGNED NULL,
  `end_date` DATE NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `events`
INSERT INTO `events` (`id`, `room_id`, `pic_id`, `event_name`, `date`, `start_time`, `end_time`, `status`, `approved_by`, `move_reason`, `created_at`, `updated_at`, `alasan_penolakan`, `session_id`, `end_date`) VALUES
('1', '1', '5', 'Pelatihan Cloud Computing & DevOps', '2026-11-07 00:00:00', '08:00:00', '11:30:00', 'approved', '1', NULL, '2026-11-06 08:55:37', '2026-11-06 08:55:37', NULL, NULL, NULL),
('2', '4', '1', 'Rapat Koordinasi Persiapan UKK', '2026-11-08 00:00:00', '09:00:00', '12:00:00', 'approved', '1', NULL, '2026-11-06 08:55:37', '2026-11-06 08:55:37', NULL, NULL, NULL),
('3', '3', '6', 'Latihan Geladi Bersih Pentas Seni', '2026-11-09 00:00:00', '13:00:00', '16:00:00', 'pending', NULL, NULL, '2026-11-06 08:55:37', '2026-11-06 08:55:37', NULL, NULL, NULL);

-- --------------------------------------------------------
-- Table structure for `complaints`
-- --------------------------------------------------------
CREATE TABLE `complaints` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id` BIGINT UNSIGNED NOT NULL,
  `reporter_id` BIGINT UNSIGNED NOT NULL,
  `description` TEXT NOT NULL,
  `photo_path` VARCHAR(255) NULL,
  `status_repair` VARCHAR(255) NOT NULL DEFAULT 'Belum Diperbaiki',
  `handler_id` BIGINT UNSIGNED NULL,
  `reported_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `event_id` BIGINT UNSIGNED NULL,
  `repair_notes` TEXT NULL,
  `repaired_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `complaints`
INSERT INTO `complaints` (`id`, `room_id`, `reporter_id`, `description`, `photo_path`, `status_repair`, `handler_id`, `reported_at`, `created_at`, `updated_at`, `event_id`, `repair_notes`, `repaired_at`) VALUES
('1', '1', '5', 'AC unit 2 bocor dan menetes ke dekat stopkontak deret C. Khawatir korsleting saat praktikum.', NULL, 'Belum Diperbaiki', '3', '2026-11-03 08:55:37', '2026-11-06 08:55:37', '2026-11-06 08:55:37', NULL, NULL, NULL),
('2', '4', '1', 'Proyektor mati mendadak saat rapat komite. Lampu indikator berkedip merah terus.', NULL, 'Menunggu Spare Part', '4', '2026-11-05 08:55:37', '2026-11-06 08:55:37', '2026-11-06 08:55:37', NULL, NULL, NULL),
('3', '2', '5', 'Kabel LAN PC nomor 12 putus terjepit kaki meja.', NULL, 'Sudah Diperbaiki', '3', '2026-11-02 08:55:37', '2026-11-06 08:55:37', '2026-11-06 08:55:37', NULL, NULL, '2026-11-06 08:55:37');

-- --------------------------------------------------------
-- Table structure for `api_tokens`
-- --------------------------------------------------------
CREATE TABLE `api_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL DEFAULT 'mobile_app',
  `token` VARCHAR(255) NOT NULL,
  `last_used_at` DATETIME NULL,
  `expires_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `migrations`
-- --------------------------------------------------------
CREATE TABLE `migrations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` VARCHAR(255) NOT NULL,
  `batch` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `migrations`
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
('1', '0001_01_01_000001_create_cache_table', '1'),
('2', '0001_01_01_000002_create_jobs_table', '1'),
('3', '2026_01_01_000001_create_roles_table', '1'),
('4', '2026_01_01_000002_create_users_table', '1'),
('5', '2026_01_01_000003_create_rooms_table', '1'),
('6', '2026_01_01_000004_create_events_table', '1'),
('7', '2026_01_01_000005_create_complaints_table', '1'),
('8', '2026_01_01_000006_create_api_tokens_table', '1'),
('9', '2026_01_01_000007_add_alasan_penolakan_to_events_table', '1'),
('10', '2026_01_01_000008_create_booking_sessions_table', '1'),
('11', '2026_01_01_000009_add_session_id_to_events_table', '1'),
('12', '2026_01_01_000010_add_event_id_to_complaints_table', '1'),
('13', '2026_01_01_000011_add_email_to_users_and_password_resets', '1'),
('14', '2026_11_04_100336_add_end_date_to_events_table', '1'),
('15', '2026_11_04_100346_add_repair_details_to_complaints_table', '1');

SET FOREIGN_KEY_CHECKS=1;
