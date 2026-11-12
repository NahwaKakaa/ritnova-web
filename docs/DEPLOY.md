# Deploy RITNOVA

Produksi: **https://ritnova.online** di hosting Rumahweb Unlimited M (cPanel, server `kambaniru`),
satu akun dengan web Lonjoboko Rescue.

> **Status 12 Nov 2026:** online di https://ritnova.online (addon domain, subdomain internal `ritnovaweb`,
> SSL AutoSSL, paksa HTTPS aktif). Alamat sementara https://ritnovaonline.lonjoboko-rescue.web.id tetap ada
> dan memakai folder yang sama; host lain dialihkan ke https://ritnova.online/.

## Susunan di server

```
~/ritnova/                       kode Laravel (di luar folder web)
~/ritnova/.env                   konfigurasi produksi (chmod 600, tidak ada di git)
~/lonjoboko-rescue/public/       = public_html, milik web Lonjoboko (jangan diubah)
  └─ ritnova-online  ->  ~/ritnova/public     shortcut (symlink) document root ritnova.online
```

Server ini membatasi document root domain hanya di dalam `public_html`, sedangkan `public_html`
adalah folder publik Lonjoboko. Karena itu RITNOVA memakai satu symlink `ritnova-online`.
`.htaccess` RITNOVA mengalihkan semua host selain `ritnova.online` ke https://ritnova.online/,
sehingga folder itu tidak bisa dibuka lewat domain Lonjoboko.

> Bila suatu saat deploy Lonjoboko menghapus isi `public/` (mis. `git clean`), buat ulang:
> `ln -sn /home/lonz8291/ritnova/public ~/lonjoboko-rescue/public/ritnova-online`

- Database: `lonz8291_ritnova` (user `lonz8291_ritnova`), MySQL di `localhost`.
- PHP CLI 8.3 (`/usr/local/bin/php`). `exec()` dinonaktifkan hosting, jadi `storage:link` tidak dipakai.
- Cron (cPanel → Cron Jobs), setiap menit:
  `cd /home/lonz8291/ritnova && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1`
- Cadangan database harian 02.30 WITA → `~/ritnova/storage/app/backups` (14 terakhir). Unduh berkala.

## Memperbarui versi

1. GitHub → Actions → **Paket deploy** → Run workflow. Hasilnya `ritnova-deploy.zip` di Releases.
2. cPanel → Terminal (ganti `TAG` dengan nama release terbaru):

```bash
cd ~ && TAG=deploy-YYYYMMDD-HHMM \
&& curl -fsSL -o rit.zip https://github.com/NahwaKakaa/ritnova-web/releases/download/$TAG/ritnova-deploy.zip \
&& cd ritnova && php artisan down \
&& unzip -oq ../rit.zip -x '.env*' 'storage/*' && rm ../rit.zip \
&& php artisan migrate --force && php artisan optimize:clear && php artisan config:cache \
&& php artisan route:cache && php artisan view:cache && php artisan up && php artisan ritnova:check
```

`.env` dan `storage/` (log, cadangan) tidak ikut ditimpa.

## Pemasangan awal (sudah dilakukan 12 Nov 2026)

```bash
cd ~ && curl -fsSL -o ritnova.zip <url ritnova-deploy.zip> && mkdir ritnova && cd ritnova
unzip -q ../ritnova.zip && rm ../ritnova.zip
cp .env.production.example .env && chmod 600 .env    # isi DB_*, APP_URL, RITNOVA_ADMIN_PASSWORD
php artisan key:generate --force
php artisan migrate --force --seed
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan ritnova:check
```

Lalu: domain `ritnova.online` (cPanel → Domains, document root `public_html/ritnova-online`, tidak berbagi
document root), versi PHP domain lewat MultiPHP Manager, SSL lewat AutoSSL, dan cron di atas.

## Setelah penelitian selesai
1. Ekspor seluruh dataset + kamus data dari panel peneliti, serahkan ke tim peneliti.
2. `php artisan down`.
3. Hapus domain ritnova.online di cPanel, symlink `~/lonjoboko-rescue/public/ritnova-online`,
   folder `~/ritnova`, cron RITNOVA, dan database `lonz8291_ritnova`.
