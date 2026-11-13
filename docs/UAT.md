# UAT RITNOVA (User Acceptance Test)

Lingkungan: https://ritnova.online · Penguji: tim peneliti (peran peneliti dan admin) dan 2–3 peserta uji (minimal satu intervensi, satu pembanding).
Isi kolom **Hasil** dengan ✅ lulus / ❌ gagal, dan tulis temuan di kolom Catatan. Temuan yang diperbaiki dicatat di bagian *Catatan revisi*.

Siapkan dulu: kohort uji, kode peserta uji (impor CSV), instrumen T0 dengan butir resmi, dokumen persetujuan versi resmi, konten minimal sesi 1.

## A. Peserta: akses dan persetujuan
| ID | Skenario | Langkah | Hasil yang diharapkan | Hasil | Catatan |
|---|---|---|---|---|---|
| P01 | Login benar | Masuk dengan kode dan kata sandi sementara | Masuk ke halaman persetujuan | | |
| P02 | Login salah berulang | Salah kata sandi > 10 kali dalam 1 menit | Diminta menunggu; tidak masuk | | |
| P03 | Persetujuan wajib | Buka /beranda sebelum setuju | Dialihkan ke halaman persetujuan | | |
| P04 | Setuju | Centang lalu setuju | Lanjut ke persiapan | | |
| P05 | Unduh PDF informasi | Pilih unduh PDF di halaman persetujuan | PDF versi aktif terunduh | | |
| P06 | Versi baru | Admin menerbitkan versi baru, peserta masuk lagi | Diminta setuju ulang; pesan "diperbarui dari versi …" tampil | | |
| P07 | Ganti kata sandi | Menu → Profil → ganti kata sandi | Bisa masuk dengan kata sandi baru | | |

## B. Peserta: persiapan, diary, pengukuran
| ID | Skenario | Langkah | Hasil yang diharapkan | Hasil | Catatan |
|---|---|---|---|---|---|
| P08 | Persiapan | Selesaikan 4 langkah | Beranda terbuka | | |
| P09 | Diary lintas tengah malam | Tidur 23.30, bangun 06.15 | Tersimpan; durasi benar | | |
| P10 | Diary tidak lengkap | Isi jam tidur saja | Status belum lengkap; muncul di daftar "Lengkapi catatan" | | |
| P11 | Ubah diary | Ubah jam bangun malam kemarin | Nilai baru tampil; admin melihat riwayat revisi | | |
| P12 | Isi T0 | Saat jendela T0 dibuka, isi instrumen | Tersimpan; tidak bisa dikirim dua kali | | |
| P13 | Skor T0 | Admin membuka Pengukuran / ekspor skor | Skor sesuai hitungan manual dari kunci instrumen | | |
| P14 | Jendela tertutup | Buka instrumen di luar jendela | Ditolak dengan pesan yang jelas | | |

## C. Peserta intervensi
| ID | Skenario | Langkah | Hasil yang diharapkan | Hasil | Catatan |
|---|---|---|---|---|---|
| P15 | Sesi terkunci | Buka sesi yang belum rilis | Hitung mundur; tidak bisa dimulai | | |
| P16 | Selesaikan sesi | Tonton materi, cek pemahaman, aksi | Sesi selesai; perayaan; fase bulan bertambah | | |
| P17 | SJL kurang data | Diary < 2 hari kuliah atau < 1 hari bebas | Pesan data belum cukup (bukan angka 0) | | |
| P18 | SJL cukup data | Lengkapi diary | SJL tampil; sesuai hitungan manual | | |
| P19 | Target & refleksi | Buat target, tinjau, isi refleksi | Tersimpan dan tampil di beranda/progres | | |
| P20 | Kelompok pembanding | Peserta pembanding membuka /belajar | Tidak bisa diakses | | |

## D. Peserta: lain-lain
| ID | Skenario | Langkah | Hasil yang diharapkan | Hasil | Catatan |
|---|---|---|---|---|---|
| P21 | Pengingat | Atur jam pengingat | Tersimpan | | |
| P22 | Mode malam | Tarik lampu / colokkan steker | Tema berganti dan diingat | | |
| P23 | Ponsel | Ulangi P08–P11 di ponsel | Semua bisa dipakai tanpa geser horizontal | | |
| P24 | Tarik persetujuan | Profil → tarik persetujuan | Keluar; tidak bisa masuk lagi; status di admin "mengundurkan diri" | | |

## E. Peneliti dan admin
| ID | Skenario | Langkah | Hasil yang diharapkan | Hasil | Catatan |
|---|---|---|---|---|---|
| A01 | Peran peneliti | Masuk sebagai peneliti | Tidak ada tombol impor/hapus/terbitkan/akses tim | | |
| A02 | Impor peserta | Unggah CSV kode | Kode dibuat; kata sandi sementara tampil sekali | | |
| A03 | Kelompok | Tetapkan kelompok dengan alasan | Tercatat di audit log | | |
| A04 | Ubah/hapus peserta | Ubah kode; hapus peserta tanpa data | Berhasil; peserta dengan data tidak bisa dihapus | | |
| A05 | Jadwal | Ubah waktu buka sesi | Peserta melihat jadwal baru; audit log | | |
| A06 | Konten | Ubah materi sesi | Versi naik; peserta menerima materi baru | | |
| A07 | Instrumen | Buat instrumen dengan skor terbalik | Skor sesuai kunci; butir terkunci setelah ada respons | | |
| A08 | Hitung ulang | Ubah aturan skoring lalu hitung ulang | Versi naik; skor berubah; audit log | | |
| A09 | Persetujuan | Buat draf, unggah PDF, terbitkan | Versi aktif berganti; daftar "menunggu persetujuan" benar | | |
| A10 | FAQ | Ubah FAQ | Landing page berubah | | |
| A11 | Ekspor CSV | Unduh semua dataset | Terbuka di Excel; kosong tetap kosong; waktu +08:00 | | |
| A12 | Ekspor XLSX | Unduh "Semua dataset (XLSX)" | Satu workbook, satu sheet per dataset + kamus data | | |
| A13 | Target pengingat | Unduh per kelompok | Hanya peserta yang belum melengkapi diary semalam | | |
| A14 | Audit log | Filter per aksi/peserta | Perubahan di atas tercatat | | |
| A15 | Akses tim | Buat akun peneliti, nonaktifkan | Akun nonaktif tidak bisa masuk | | |

## F. Operasional
| ID | Skenario | Langkah | Hasil yang diharapkan | Hasil | Catatan |
|---|---|---|---|---|---|
| O01 | Pemeriksaan server | `php artisan ritnova:check` | Semua OK | | |
| O02 | Cadangan harian | Cek folder backups keesokan hari | Ada file baru 02.30 | | |
| O03 | Uji pemulihan | Ikuti `docs/PEMULIHAN.md` di lingkungan uji | Data kembali | | |
| O04 | HTTPS | Buka http://ritnova.online | Dialihkan ke https | | |

## Persetujuan UAT
| Peran | Nama | Tanggal | Tanda tangan |
|---|---|---|---|
| Peneliti utama | | | |
| Admin sistem | | | |

## Catatan revisi
| Tanggal | ID UAT | Temuan | Perbaikan | Versi rilis |
|---|---|---|---|---|
| | | | | |
