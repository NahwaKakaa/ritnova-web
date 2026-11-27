# Status Fitur

✅ = berjalan · 🟡 = kerangka/sebagian · ⬜ = belum

## Peserta (Layar 01–20 mockup)
| Layar | Fitur | Status | Catatan |
|---|---|---|---|
| 01 | Landing page (program, fitur, privasi, FAQ) | ✅ | |
| 02 | Login kode peserta, batas percobaan, auto-logout idle | ✅ | Reset kata sandi lewat peneliti |
| 03 | Persetujuan berversi + tarik persetujuan | ✅ | Versi baru diterbitkan dari panel admin; peserta wajib setuju ulang |
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
| 15 | Assessment T0/T1/T2 (form dari butir instrumen) | ✅ | Butir & skoring diisi peneliti lewat editor instrumen; skor otomatis |
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
| Pengukuran T0–T2: jendela bisa diubah, status per peserta, malam diary per fase | ✅ | Revisi 23 Nov: fase M2 (SSE minggu ke-2), T1 minggu ke-5, T2 minggu ke-8, diary 7 malam di dalam jendela, tombol jadwal standar |
| Kualitas diary: belum lengkap, durasi tak wajar, sering diubah, malam terlewat | ✅ | |
| Konten 12 sesi: judul, media, transkrip, pesan kunci, aksi, cek pemahaman; versi naik otomatis | ✅ | |
| Jadwal rilis: buka, batas opsional, prasyarat, kebijakan strict/catch-up, buat ulang jadwal standar | ✅ | Semua perubahan masuk audit log |
| Ekspor CSV/XLSX per dataset, workbook XLSX semua dataset + kamus data, target pengingat WhatsApp | ✅ | XLSX tanpa pustaka tambahan (butuh ekstensi zip PHP) |
| Audit log dengan filter | ✅ | |
| Akses tim (buat akun peneliti/admin, reset, nonaktifkan) | ✅ | |
| Editor instrumen: butir Likert/pilihan/angka/jam/teks, skor terbalik, jumlah/rata-rata, versi, hitung ulang, salin | ✅ | Butir terkunci setelah ada respons; skor menyimpan aturan yang dipakai |
| Dokumen persetujuan: draf, PDF, terbitkan versi, daftar yang belum setuju | ✅ | Versi terbit dibekukan |
| FAQ landing page | ✅ | |
| Backup database harian + pemulihan | ✅ | `ritnova:backup` 02.30 WITA, `ritnova:restore`; lihat `docs/PEMULIHAN.md` |

## Dokumen
- `docs/MANUAL-PESERTA.md`, `docs/MANUAL-PENELITI.md`: panduan pengguna
- `docs/UAT.md`: daftar uji penerimaan + catatan revisi
- `docs/PEMULIHAN.md`: cadangan dan pemulihan
- `docs/DEPLOY.md`: deploy di Rumahweb

## Masih menunggu dari tim peneliti
- Naskah persetujuan resmi (komisi etik) dan PDF informasi studi
- Butir dan kunci skoring instrumen T0–T2
- Media, transkrip, dan cek pemahaman 12 sesi

## Revisi alur peneliti (23 Nov 2026)

Sudah diterapkan:
- Persiapan awal: Persetujuan → Profil dasar → Kuesioner awal (per instrumen) → Catatan tidur T0 (x dari 7 malam, hari 1–7).
- Sleep diary: jam berbaring, mulai tidur, bangun, kewajiban saat bangun, dan alarm wajib diisi; jam dipilih dari jam dan menit; jenis hari tidak ditebak dari Senin–Minggu; catatan tetap opsional.
- Fase M2 (kuesioner minggu kedua), T1 minggu ke-5, T2 minggu ke-8; jadwal standar bisa diisi admin dengan satu tombol.
- Peserta tanpa kelompok setelah baseline: "Pengukuran awal telah selesai · menunggu program dimulai".
- Nilai SJL tidak ditampilkan sebelum program dimulai (SJL Check dan Insights).
- Cek pemahaman tidak lagi dianggap selesai bila pertanyaannya belum ada.
- Arahan harian di beranda (catat tidur, kuesioner minggu kedua, T1/T2 dimulai, x dari 7 catatan, selesai).

Menunggu peneliti: butir SSE dan rMEQ, ketentuan malam valid SJL, tanggal final, data profil (nama, TTL, WA) setelah consent diperbarui, dasbor dan edukasi standar kelompok kontrol, materi 12 sesi, target spesifik pada tahap Aksi.

## Profil dasar dan Skrining Kelayakan (27 Nov 2026)

- Persiapan awal menjadi: Persetujuan → Profil dasar → Skrining kelayakan → Kuesioner awal → Catatan tidur T0.
- Profil dasar menambah TTL, jenis kelamin, kelas (A/B/C), tempat tinggal (rumah orang tua, kost/kontrakan, apartemen, lainnya), dan nomor WhatsApp (08…). Usia dihitung dari tanggal lahir. Tabel `participant_profiles`.
- Skrining kelayakan memakai 8 butir Lampiran 02 apa adanya. Aturan bagian C ada di `app/Domain/Screening/EligibilityRules.php` (diuji di `tests/Unit/EligibilityRulesTest.php`). Tabel `eligibility_screenings`.
- Keputusan peneliti: hasil skrining hanya penanda untuk petugas, peserta tetap lanjut; jawaban tidak bisa diubah peserta.
- Admin: halaman "Skrining & rekrutmen" (rekap per kelas seperti bagian D, daftar hasil), profil dan jawaban skrining di halaman peserta.
- Ekspor: dataset baru `screening`; dataset `participants` menambah kelas, jenis kelamin, usia saat mendaftar, tempat tinggal. Nama tempat lahir, tanggal lahir, dan nomor WhatsApp tidak diekspor.
- Masih menunggu: butir SSE dan rMEQ, serta timeline resmi (jendela T0, M2, T1, T2).

## Instrumen SSE-SJL versi 1.0 (27 Nov 2026)

- Butir dari Lampiran 06 (16 butir uji coba, skala 0–10, skor rerata, fase T0/M2/T1/T2) disimpan di `database/instruments/sse-sjl.php`.
- Pasang di server dengan `php artisan ritnova:instrument database/instruments/sse-sjl.php`. Perintah tidak menimpa instrumen yang sudah berisi butir.
- Skala angka panjang (lebih dari 7 pilihan) tampil sebagai satu baris tombol angka dengan keterangan di 0, 5, 10.
- Versi 2.0 dari peneliti (setelah uji validitas dan reliabilitas) diubah lewat menu Instrumen di admin; versi naik otomatis.

## Instrumen rMEQ (27 Nov 2026)

- Butir dari Lampiran 07 (5 butir baku, pilihan tunggal, nilai pilihan = kunci skor) disimpan di `database/instruments/rmeq.php`. Kode `RMEQ`, fase T0 saja.
- Skor total = jumlah kelima butir (4–25). Kategori deskriptif (4–11 Malam, 12–17 Antara, 18–25 Pagi) dihitung peneliti dari skor di ekspor.
- Pasang dengan `php artisan ritnova:instrument database/instruments/rmeq.php`.
