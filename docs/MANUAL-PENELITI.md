# Panduan Panel Peneliti dan Admin RITNOVA

Alamat: **https://ritnova.online/admin**. Masuk dengan akun tim (bukan kode peserta).

## Peran
| Peran | Bisa |
|---|---|
| **Peneliti** | Melihat semua halaman pemantauan, mengelola konten sesi, instrumen, FAQ, mengekspor data, membaca audit log |
| **Admin** | Semua hak peneliti, ditambah: impor/ubah/hapus peserta, kelompok, status, reset kata sandi, jadwal rilis, jendela pengukuran, dokumen persetujuan, akun tim |

Pemilih **kohort** di bagian atas berlaku untuk semua halaman pemantauan dan ekspor.

## Pemantauan
- **Ringkasan kohort**: persetujuan, diary valid, sesi selesai, penyelesaian per minggu, dan daftar tindak lanjut.
- **Peserta & kelompok**: filter fase studi; mode buta menyembunyikan kelompok. Pilih kode peserta untuk detail (paparan 12 sesi, diary, SJL, pengukuran, target, jejak audit).
  - *Impor kode* (admin): unggah CSV berisi kode peserta. Kata sandi sementara hanya tampil sekali; simpan dan bagikan secara aman.
  - *Kelola* (admin): ubah kode, tetapkan kelompok dengan alasan, reset kata sandi, catat pengunduran diri. Hapus peserta hanya bisa bila belum ada data penelitian.
- **Paparan sesi**: dijadwalkan, selesai, terlambat, dan anomali per minggu (anomali = selesai dengan waktu aktif < 2 menit atau ditandai deviasi).
- **Pengukuran T0–T2**: status pengisian per peserta dan jumlah malam diary per fase. Admin mengubah jendela pengukuran di sini.
- **Kualitas diary**: catatan belum lengkap, durasi tidak wajar, sering diubah, malam terlewat.

## Pengaturan studi
### Konten sesi
Ubah judul, media (URL YouTube/video/audio/gambar), transkrip, pesan kunci, aksi, dan butir cek pemahaman. Versi konten naik otomatis setiap disimpan, dan paparan peserta mencatat versi yang mereka terima.

### Instrumen T0–T2
1. **Tambah instrumen**: isi kode, nama, petunjuk, dan fase (T0/T1/T2).
2. Tambahkan butir **persis seperti instrumen tervalidasi**. Jenis butir: skala (Likert), pilihan ganda, angka, jam, teks bebas. Pilihan ditulis satu per baris, format `nilai = label` (contoh `1 = Tidak pernah`).
3. Tandai butir *skor terbalik* bila instrumen memintanya (nilai dibalik: min + maks − jawaban).
4. Pilih aturan skoring: tanpa skor, jumlah, atau rata-rata. Kosongkan "butir yang dihitung" untuk memakai semua butir angka, atau tulis nomornya (contoh `1, 3, 5`).

Skor dihitung otomatis saat peserta mengirim. Bila ada jawaban yang dihitung tetapi kosong, skor dibiarkan kosong (bukan nol). Setelah ada respons, butir dikunci; mengubah aturan skoring menaikkan versi, lalu gunakan **Hitung ulang skor**. Untuk merevisi butir, gunakan **Buat salinan**. Setiap skor menyimpan salinan aturan yang dipakai.

### Dokumen persetujuan
1. **Buat versi baru** (admin): isi awal disalin dari versi aktif. Tempel naskah resmi yang disetujui komisi etik dan unggah PDF informasi studi bila ada.
2. Simpan sebagai draf; peserta belum melihatnya.
3. **Terbitkan**: versi ini menjadi aktif dan semua peserta aktif diminta menyetujui ulang saat masuk berikutnya.

Versi yang sudah terbit dibekukan sebagai bukti. Halaman ini juga menampilkan peserta yang belum menyetujui versi aktif.

### Jadwal rilis (admin)
Atur waktu buka sesi, batas opsional, prasyarat, dan kebijakan *strict* atau *catch-up*. **Buat ulang jadwal standar** menyusun 12 sesi dari tanggal mulai kohort.

### FAQ landing page
Tambah, ubah urutan, sembunyikan, atau hapus pertanyaan. Perubahan langsung tampil di halaman depan.

## Data
### Ekspor dataset
- Setiap dataset bisa diunduh sebagai **CSV** atau **XLSX**. **Semua dataset (XLSX)** membuat satu workbook berisi semua dataset dan kamus data.
- Nilai yang tidak ada dibiarkan kosong. Waktu dalam ISO 8601 dengan +08:00 (WITA). SJL membawa versi rumusnya; skor instrumen membawa aturan skoringnya.
- **Target pengingat WhatsApp**: daftar peserta yang belum melengkapi catatan tidur semalam, bisa dipisah per kelompok.
- Setiap unduhan tercatat di audit log.

### Audit log
Semua perubahan data penting: siapa, kapan, sebelum dan sesudah. Bisa difilter.

### Akses tim (admin)
Buat akun peneliti atau admin, reset kata sandi, dan nonaktifkan akun yang tidak dipakai lagi.

## Rutinitas yang disarankan
- **Setiap pagi**: Ringkasan kohort → tindak lanjut; ekspor target pengingat dan kirim WhatsApp.
- **Setiap minggu**: Paparan sesi dan Kualitas diary.
- **Setiap bulan**: unduh cadangan dari hosting (lihat `docs/PEMULIHAN.md`).
