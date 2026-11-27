<?php

// Skala Sleep Self-Efficacy Khusus Pengaturan Jadwal Tidur (SSE-SJL), versi uji coba 16 butir.
// Sumber: Lampiran 06 dari peneliti (Syabil Gema Syuhada), diterima 27 Nov 2026 sebagai versi 1.0.
// Teks butir disalin apa adanya. Kode butir dan tanda * (butir cadangan) hanya untuk dokumentasi,
// tidak ditampilkan ke peserta. Pasang dengan: php artisan ritnova:instrument database/instruments/sse-sjl.php

$scale = [
    'values' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
    'labels' => ['0 = Sama sekali tidak yakin mampu', '1', '2', '3', '4', '5 = Cukup yakin mampu', '6', '7', '8', '9', '10 = Sangat yakin mampu'],
];

return [
    'code' => 'SSE-SJL',
    'name' => 'Skala Sleep Self-Efficacy Khusus Pengaturan Jadwal Tidur (SSE-SJL)',
    'description' => "Kuesioner ini menanyakan SEBERAPA YAKIN ANDA MAMPU mengatur jadwal dan kebiasaan tidur Anda dalam rutinitas perkuliahan saat ini, bukan kebiasaan tidur Anda sekarang dan bukan pula seberapa sering Anda melakukannya.\n\nBayangkan kondisi perkuliahan, jadwal kelas, tugas, dan aktivitas sehari-hari Anda saat ini. Pilih satu angka dari 0 sampai 10 yang paling menggambarkan tingkat keyakinan Anda saat ini pada setiap pernyataan. Tidak ada jawaban benar atau salah.\n\nDalam rutinitas perkuliahan Anda saat ini, seberapa yakin Anda mampu melakukan hal-hal berikut secara konsisten?",
    'phases' => ['T0', 'M2', 'T1', 'T2'],
    // Skor = rerata seluruh butir (0,00–10,00), tanpa butir terbalik (Lampiran 06 bagian E).
    'scoring' => ['method' => 'mean'],
    'items' => [
        ['Menetapkan jam tidur yang realistis pada malam hari kuliah agar tetap memperoleh durasi tidur yang cukup sebelum kelas pagi.', 'likert', $scale], // SSE-01
        ['Memulai tidur pada jam yang relatif konsisten sepanjang malam-malam hari kuliah meskipun jam mulai kelas setiap hari berbeda.', 'likert', $scale], // SSE-02
        ['Menjaga waktu bangun pada hari bebas agar tidak bergeser lebih dari 1 jam dibandingkan waktu bangun pada hari kuliah.', 'likert', $scale], // SSE-03
        ['Tetap memulai tidur mendekati jadwal rutin pada malam hari bebas meskipun tidak ada kewajiban kuliah pagi keesokan harinya.', 'likert', $scale], // SSE-04*
        ['Menyiapkan kondisi kamar atau tempat tidur yang mendukung tidur (seperti meredupkan cahaya dan mengurangi kebisingan) menjelang jam tidur target.', 'likert', $scale], // SSE-05
        ['Membatasi konsumsi minuman berkafein (seperti kopi, teh pekat, atau minuman berenergi) sejak sore hingga malam hari agar tidak menunda rasa kantuk.', 'likert', $scale], // SSE-06
        ['Membatasi tidur siang agar tidak terlalu lama atau terlalu sore sehingga tidak menggeser jam tidur pada malam hari.', 'likert', $scale], // SSE-07*
        ['Menghentikan penggunaan gawai (media sosial, video, atau gim) setidaknya 30 menit sebelum jam tidur yang telah direncanakan.', 'likert', $scale], // SSE-08
        ['Mengatur waktu pengerjaan tugas kuliah lebih awal agar tidak terus-menerus menunda jam tidur hingga larut malam.', 'likert', $scale], // SSE-09
        ['Menenangkan pikiran dari kekhawatiran terkait perkuliahan atau aktivitas esok hari ketika sudah memasuki waktu tidur.', 'likert', $scale], // SSE-10*
        ['Membatasi atau menolak ajakan aktivitas sosial pada malam hari (seperti nongkrong atau bermain bersama teman) ketika sudah mendekati jam tidur target.', 'likert', $scale], // SSE-11
        ['Mempertahankan keteraturan jam tidur–bangun meskipun sedang menghadapi minggu dengan beban tugas atau ujian yang padat.', 'likert', $scale], // SSE-12
        ['Memantau jam tidur dan jam bangun sendiri secara rutin agar segera menyadari jika jadwal tidur mulai bergeser.', 'likert', $scale], // SSE-13
        ['Menyesuaikan kembali rutinitas malam secara mandiri ketika menyadari pola tidur dalam beberapa hari terakhir mulai tidak teratur.', 'likert', $scale], // SSE-14
        ['Mempertahankan kebiasaan tidur yang sudah berjalan baik secara mandiri tanpa perlu terus-menerus diingatkan oleh orang lain.', 'likert', $scale], // SSE-15*
        ['Kembali ke jadwal tidur–bangun yang teratur pada hari kuliah berikutnya setelah sempat tidur lebih larut pada hari bebas atau acara khusus.', 'likert', $scale], // SSE-16
    ],
];
