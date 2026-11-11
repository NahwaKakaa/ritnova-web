# RITNOVA — Website Program Kesehatan Tidur

Website intervensi kesehatan tidur untuk mahasiswa: 12 sesi edukasi dalam 4 minggu, diary tidur, kalkulator *social jetlag* (SJL), pengukuran T0/T1/T2, dan panel peneliti untuk monitoring & ekspor data.

**Stack:** Laravel 13 · PHP 8.3 · MySQL/MariaDB (produksi) / SQLite (lokal & tes) · Blade + Tailwind CSS 4 · Vite

## Menjalankan di komputer lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Akun contoh (hanya `APP_ENV=local`):

| Akun | Username | Password |
|---|---|---|
| Admin | `admin` | nilai `RITNOVA_ADMIN_PASSWORD` di `.env` |
| Peserta intervensi | `RV-001`, `RV-003` | `password` |
| Peserta pembanding | `RV-002`, `RV-004` | `password` |

Jalankan tes: `php artisan test`

## Struktur penting

| Lokasi | Isi |
|---|---|
| `app/Domain/Sleep` | Kalkulator SJL (midpoint lintas tengah malam, validasi, data belum cukup ≠ 0) |
| `app/Domain/Sessions` | Aturan penguncian sesi (waktu + prasyarat, strict/catch-up) dan aturan penyelesaian sesi |
| `app/Services` | `LearningPath` (status 12 sesi per peserta), `SjlService`, `ScheduleGenerator` |
| `app/Http/Controllers/Participant` | Halaman peserta |
| `app/Http/Controllers/Admin` | Panel peneliti |
| `app/Http/Middleware` | `role`, `consented`, `group` (cegah kebocoran konten ke kelompok pembanding) |
| `config/ritnova.php` | Parameter penelitian (kebijakan keterlambatan, ambang media, minimal hari valid SJL) |
| `database/seeders/DatabaseSeeder.php` | Admin, kohort, 12 sesi, jadwal Senin/Rabu/Jumat 19.00, jendela T0–T2 |
| `docs/` | Status fitur & panduan deploy |

## Keputusan protokol yang masih terbuka

Nilai berikut sudah bisa diatur tanpa mengubah kode, tetapi harus diputuskan bersama peneliti/pembimbing sebelum penelitian dimulai:

- Kebijakan keterlambatan: `strict` atau `catch_up` (`RITNOVA_LATE_POLICY` / kolom `late_policy` kohort)
- Ambang penyelesaian media (`RITNOVA_MEDIA_COMPLETION_THRESHOLD`, default 0,95)
- Minimal hari valid untuk SJL (`RITNOVA_SJL_MIN_WORKDAYS`, `RITNOVA_SJL_MIN_FREE_DAYS`)
- Jendela pengukuran T0/T1/T2 dan isi instrumen (diisi peneliti, jangan mengarang butir)
- Dokumen consent resmi (seeder hanya membuat draf)

Lihat [`docs/STATUS.md`](docs/STATUS.md) dan [`docs/DEPLOY.md`](docs/DEPLOY.md).
