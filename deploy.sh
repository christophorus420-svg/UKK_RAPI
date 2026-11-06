#!/bin/bash
set -e

echo "🚀 Memulai Deployment RAPI-Sekolah..."

# 1. Masuk ke mode pemeliharaan
php artisan down || true

# 2. Ambil update kode terbaru
git pull origin main || git pull origin master || true

# 3. Install dependency PHP (tanpa dev)
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# 4. Compile frontend asset Vite
if command -v npm &> /dev/null; then
    npm ci || npm install
    npm run build
fi

# 5. Jalankan migrasi database
php artisan migrate --force

# 6. Buat storage symlink
php artisan storage:link --force

# 7. Bersihkan & pasang cache produksi
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 8. Matikan mode pemeliharaan
php artisan up

echo "✅ Deployment RAPI-Sekolah Berhasil Diselesaikan!"

