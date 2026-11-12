# Status Fitur

✅ = berjalan · 🟡 = kerangka/sebagian · ⬜ = belum

## Peserta (Layar 01–20 mockup)
| Layar | Fitur | Status | Catatan |
|---|---|---|---|
| 01 | Landing page (program, fitur, privasi, FAQ) | ✅ | |
| 02 | Login kode peserta, batas percobaan, auto-logout idle | ✅ | Reset kata sandi lewat peneliti |
| 03 | Persetujuan berversi + tarik persetujuan | ✅ | Ganti dokumen draf dengan versi resmi; unggah PDF informasi studi |
| 04 | Onboarding 4 langkah + baseline T0 | ✅ | Jendela diary T0 sementara: 7 hari sebelum jendela dibuka s.d. ditutup |
| 05 | Dashboard (tugas harian, modul, diary, target, jadwal T0–T2) | ✅ | |
| 06 | Katalog 12 sesi per minggu (M1–M4) | ✅ | |
| 07 / 20 | Sesi 3 tahap + pelacakan media & waktu aktif | 🟡 | Pemutar siap (YouTube/video/audio/infografis); URL media, transkrip, kuis per sesi menunggu konten final |
| 08 | Form catatan tidur (lintas tengah malam, saran hari bebas akhir pekan) | ✅ | |
| 09 | Riwayat diary minggu ini + 5 minggu, daftar malam belum lengkap | ✅ | |
| 10–11 | SJL Check dari diary + kalkulator simulasi (server) | ✅ | |
| 12 | Goals: target, rencana aksi, jika–maka, review | ✅ | |
| 13 | Refleksi mingguan | ✅ | |
| 14 | Insights SJL T0/T1/T2 | ✅ | Grafik server-side; data nyata dari diary |
| 15 | Assessment T0/T1/T2 (form dari butir instrumen) | 🟡 | Mesin form siap; butir & skoring diisi peneliti |
| 16 | Pengingat: preferensi + kotak notifikasi web | ✅ | Pengiriman WhatsApp oleh klien |
| 17 | Bantuan, privasi, profil, ubah kata sandi | ✅ | |
| 18 | Progres 12 sesi + alasan status | ✅ | |
| 19 | Sesi terkunci + countdown | ✅ | |

Catatan: bila sesi belum punya butir cek pemahaman, tahap 02 dianggap terpenuhi agar sesi tetap bisa diselesaikan.

### Interaksi tampilan
- Latar mengikuti waktu WITA (pagi/siang/sore/malam), panel malam berbintang, jam WITA berjalan di beranda.
- Jam ritme punya jarum "sekarang" dan keterangan saat disentuh.
- Simulator SJL dengan slider (landing & SJL Check); hitungan tetap di server (`POST /sjl/simulasi`).
- Form catatan tidur: garis waktu 18.00–12.00 yang bisa digeser (mengisi input jam; validasi di server).
- Grafik tracker tumbuh saat dibuka dan menampilkan detail tiap malam.
- Progres: 12 fase bulan, satu per sesi; perayaan singkat setelah sesi selesai.
- Mode terang dan malam: lampu bertali di desktop, stop kontak di ponsel. Pilihan disimpan di browser; tanpa pilihan, mengikuti pengaturan perangkat.
- Semua animasi mati otomatis bila perangkat memakai mode kurangi gerakan.

## Peneliti (Layar admin 01–04, tambahan 21–22)
Peran: **peneliti** melihat semua data, mengelola konten sesi, dan mengekspor. **Admin** juga mengubah peserta, kelompok, jadwal, jendela pengukuran, dan akun tim.

| Fitur | Status | Catatan |
|---|---|---|
| Ringkasan kohort: consent, diary valid, sesi selesai, penyelesaian per minggu, tindak lanjut | ✅ | Populasi sasaran dari `RITNOVA_TARGET_POPULATION` |
| Peserta & kelompok: filter fase studi, mode buta, impor kode CSV, penetapan kelompok + alasan, reset kata sandi, pengunduran diri | ✅ | Kata sandi sementara tampil sekali |
| Detail peserta: paparan 12 sesi, diary, SJL, pengukuran, target, jejak audit | ✅ | |
| Paparan intervensi per minggu (dijadwalkan, selesai, terlambat, anomali) | ✅ | Anomali: selesai < 2 menit aktif atau ditandai deviasi |
| Pengukuran T0–T2: jendela bisa diubah, status per peserta, malam diary per fase | ✅ | Butir & skoring instrumen diisi peneliti (belum ada editor instrumen) |
| Kualitas diary: belum lengkap, durasi tak wajar, sering diubah, malam terlewat | ✅ | |
| Konten 12 sesi: judul, media, transkrip, pesan kunci, aksi, cek pemahaman; versi naik otomatis | ✅ | |
| Jadwal rilis: buka, batas opsional, prasyarat, kebijakan strict/catch-up, buat ulang jadwal standar | ✅ | Semua perubahan masuk audit log |
| Ekspor CSV 10 dataset + kamus data, target pengingat WhatsApp | ✅ | XLSX belum; CSV ber-BOM terbuka langsung di Excel |
| Audit log dengan filter | ✅ | |
| Akses tim (buat akun peneliti/admin, reset, nonaktifkan) | ✅ | |
| Editor instrumen (butir & skoring) | ⬜ | |
| Backup database harian | ⬜ | Atur di hosting (cron mysqldump) |
