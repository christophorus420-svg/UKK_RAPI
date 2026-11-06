<?php
/**
 * RAPI-Sekolah - cPanel Setup & Maintenance Helper
 * File ini membantu inisialisasi database (migrate/seed) dan storage symlink di shared hosting cPanel tanpa SSH.
 * HAPUS file ini setelah setup selesai demi keamanan!
 */

// Cari path Laravel
$basePath = __DIR__ . '/..';
if (!file_exists($basePath . '/vendor/autoload.php') && file_exists(__DIR__ . '/../laravel_app/vendor/autoload.php')) {
    $basePath = __DIR__ . '/../laravel_app';
}

if (!file_exists($basePath . '/vendor/autoload.php')) {
    die("<h1>Error: Folder Laravel tidak ditemukan</h1><p>Pastikan file Laravel berada di direktori yang sesuai (misal: <code>../laravel_app</code>).</p>");
}

require $basePath . '/vendor/autoload.php';
$app = require_once $basePath . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$output = '';
$action = $_GET['action'] ?? '';

if ($action === 'migrate_seed') {
    try {
        Illuminate\Support\Facades\Artisan::call('migrate --force');
        $output .= "=== HASIL MIGRATE ===\n" . Illuminate\Support\Facades\Artisan::output() . "\n";

        Illuminate\Support\Facades\Artisan::call('db:seed --force');
        $output .= "=== HASIL SEEDER ===\n" . Illuminate\Support\Facades\Artisan::output() . "\n";
    } catch (\Throwable $e) {
        $output .= "ERROR DATABASE: " . $e->getMessage() . "\n";
    }
} elseif ($action === 'storage_link') {
    try {
        $target = $basePath . '/storage/app/public';
        $link = __DIR__ . '/storage';

        if (file_exists($link)) {
            $output .= "Symlink atau folder 'storage' di public_html sudah ada.\n";
        } else {
            if (@symlink($target, $link)) {
                $output .= "Berhasil membuat symlink storage!\nTarget: $target\nLink: $link\n";
            } else {
                Illuminate\Support\Facades\Artisan::call('storage:link');
                $output .= "Artisan storage:link output:\n" . Illuminate\Support\Facades\Artisan::output() . "\n";
            }
        }
    } catch (\Throwable $e) {
        $output .= "ERROR STORAGE LINK: " . $e->getMessage() . "\n";
    }
} elseif ($action === 'optimize') {
    try {
        Illuminate\Support\Facades\Artisan::call('optimize:clear');
        $output .= "=== CACHE CLEARED ===\n" . Illuminate\Support\Facades\Artisan::output() . "\n";
        Illuminate\Support\Facades\Artisan::call('config:cache');
        Illuminate\Support\Facades\Artisan::call('route:cache');
        Illuminate\Support\Facades\Artisan::call('view:cache');
        $output .= "=== CACHE GENERATED ===\n" . Illuminate\Support\Facades\Artisan::output() . "\n";
    } catch (\Throwable $e) {
        $output .= "ERROR OPTIMIZE: " . $e->getMessage() . "\n";
    }
} elseif ($action === 'self_destruct') {
    if (@unlink(__FILE__)) {
        die("<h1>File cpanel_setup.php Berhasil Dihapus</h1><p>Website Anda sekarang aman. Silakan buka <a href='/'>Halaman Utama</a>.</p>");
    } else {
        $output .= "Gagal menghapus file secara otomatis. Silakan hapus file cpanel_setup.php secara manual melalui cPanel File Manager.\n";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>cPanel Setup Helper - RAPI Sekolah</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f1f5f9; color: #1e293b; padding: 30px 15px; margin: 0; }
        .container { max-width: 700px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 24px; }
        h1 { font-size: 20px; color: #1e3a8a; margin-top: 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
        .desc { font-size: 14px; color: #64748b; line-height: 1.5; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; color: #fff; cursor: pointer; border: none; margin-right: 8px; margin-bottom: 10px; }
        .btn-primary { background: #2563eb; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #059669; }
        .btn-success:hover { background: #047857; }
        .btn-warning { background: #d97706; }
        .btn-warning:hover { background: #b45309; }
        .btn-danger { background: #dc2626; }
        .btn-danger:hover { background: #b91c1c; }
        pre { background: #0f172a; color: #38bdf8; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 13px; line-height: 1.5; }
        .alert-box { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; font-size: 13px; color: #92400e; margin-bottom: 20px; border-radius: 4px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🚀 RAPI-Sekolah - cPanel Setup Assistant</h1>
    <div class="alert-box">
        ⚠️ <strong>PENTING:</strong> Pastikan Anda sudah mengisi kredensial database yang benar di file <code>laravel_app/.env</code> sebelum menjalankan tombol di bawah.
    </div>
    <div class="desc">
        Klik tombol-tombol di bawah secara berurutan untuk menyelesaikan inisialisasi aplikasi di server cPanel Anda:
    </div>

    <div>
        <a href="?action=migrate_seed" class="btn btn-primary">1. Jalankan Migrasi & Database Seeder</a>
        <a href="?action=storage_link" class="btn btn-success">2. Buat Storage Symlink (Foto)</a>
        <a href="?action=optimize" class="btn btn-warning">3. Optimize & Clear Cache</a>
        <br><br>
        <a href="/" target="_blank" class="btn btn-primary" style="background:#0284c7;">🌐 Buka Website</a>
        <a href="?action=self_destruct" onclick="return confirm('Apakah Anda yakin ingin menghapus file cpanel_setup.php ini sekarang?')" class="btn btn-danger">🗑️ Hapus File Setup Ini (Keamanan)</a>
    </div>

    <?php if (!empty($output)): ?>
        <h3>Log Hasil Eksekusi:</h3>
        <pre><?= htmlspecialchars($output) ?></pre>
    <?php endif; ?>
</div>
</body>
</html>

