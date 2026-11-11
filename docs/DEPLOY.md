# Deploy ke Hosting cPanel (Rumahweb)

Syarat: PHP 8.3+, MySQL/MariaDB, cron per menit. Akses SSH sangat disarankan (paket Unlimited M ke atas).

## 1. Struktur folder
```
~/ritnova/            <- seluruh kode Laravel (di luar public_html)
~/ritnova/public      <- jadikan document root domain RITNOVA
```
Di cPanel → Domains: arahkan document root domain ke `ritnova/public`. Atur versi PHP domain ke 8.3 (MultiPHP Manager).

## 2. Database
cPanel → MySQL Databases: buat database & user khusus RITNOVA, beri hak hanya ke database tersebut.

## 3. Pasang aplikasi (via SSH)
```bash
cd ~ && git clone <url-repo> ritnova && cd ritnova
composer install --no-dev --optimize-autoloader
cp .env.example .env    # isi APP_ENV=production, APP_DEBUG=false, APP_URL, DB_*, RITNOVA_ADMIN_*
php artisan key:generate
php artisan migrate --force --seed
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
Aset front-end: jalankan `npm ci && npm run build` di komputer lokal/CI, lalu unggah folder `public/build`.

## 4. Cron (cPanel → Cron Jobs, setiap menit)
```
* * * * * cd ~/ritnova && php artisan schedule:run >> /dev/null 2>&1
```

## 5. Setelah penelitian selesai
1. Ekspor seluruh dataset + data dictionary, serahkan ke peneliti.
2. Aktifkan mode pemeliharaan: `php artisan down`.
3. Hapus database, folder `~/ritnova`, dan domain dari hosting.
