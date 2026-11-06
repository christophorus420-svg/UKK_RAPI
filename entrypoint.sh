#!/bin/sh
set -e

# Buat symbolic link storage jika belum ada
php artisan storage:link --force || true

# Optimasi cache konfigurasi dan rute di production
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Jalankan Nginx di background dan PHP-FPM di foreground
nginx
exec php-fpm -F

