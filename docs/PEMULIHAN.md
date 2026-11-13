# Cadangan dan Pemulihan Database

## Cadangan otomatis
- Cron hosting menjalankan scheduler Laravel setiap menit; cadangan dibuat setiap hari pukul **02.30 WITA** dengan `php artisan ritnova:backup`.
- File: `storage/app/private/backups/ritnova-YYYYMMDD-HHMMSS-mmm.jsonl.gz` (semua tabel kecuali sesi dan cache). 14 file terakhir disimpan.
- Cadangan manual kapan saja: `php artisan ritnova:backup`.
- **Simpan salinan di luar hosting** minimal sebulan sekali: cPanel → File Manager → `ritnova/storage/app/private/backups` → unduh file terbaru ke Drive tim.

## Memulihkan
Jalankan dari cPanel → Terminal di folder aplikasi (`cd ~/ritnova`):

```bash
php artisan ritnova:restore --list                     # lihat daftar cadangan
php artisan down                                       # tutup sementara untuk peserta
php artisan ritnova:restore ritnova-20261113-023000-000.jsonl.gz
php artisan optimize:clear
php artisan up
php artisan ritnova:check
```

- Perintah menampilkan jumlah baris per tabel lalu meminta konfirmasi. Isi tabel yang ada di cadangan **diganti seluruhnya**.
- Sebelum mengganti, keadaan saat ini otomatis dicadangkan dulu, jadi pemulihan bisa dibatalkan dengan memulihkan cadangan itu.
- File cadangan yang diunduh dari luar bisa diunggah ke folder `backups` lalu dipakai dengan namanya, atau diberi path lengkap.
- Bila struktur tabel belum ada (server baru), jalankan `php artisan migrate --force` lebih dulu.
- Catat pemulihan di catatan revisi UAT/operasional: tanggal, file cadangan, alasan.

## Uji pemulihan
Uji di komputer lokal atau server cadangan, bukan di production: salin file cadangan, `php artisan migrate`, lalu `php artisan ritnova:restore <file> --force`. Tes otomatis `BackupRestoreTest` juga memastikan alur ini di setiap perubahan kode.
