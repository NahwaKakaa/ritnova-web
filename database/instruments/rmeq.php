<?php

// Reduced Morningness–Eveningness Questionnaire (rMEQ), versi adaptasi Bahasa Indonesia, 5 butir baku.
// Sumber: Lampiran 07 dari peneliti (Syabil Gema Syuhada), diterima 27 Nov 2026.
// Teks butir dan pilihan disalin apa adanya; nilai pilihan = kunci skor dokumen (tidak tampil ke peserta).
// Skor total = jumlah 5 butir (4–25); kategori deskriptif 4–11 Malam, 12–17 Antara, 18–25 Pagi.
// Diukur sekali pada T0. Pasang dengan: php artisan ritnova:instrument database/instruments/rmeq.php

return [
    'code' => 'RMEQ',
    'name' => 'Kuesioner Kronotipe (rMEQ)',
    'description' => 'Kuesioner ini berisi 5 pertanyaan singkat mengenai kecenderungan alami waktu tidur, bangun, dan kesiagaan tubuh Anda. Jawablah setiap pertanyaan berdasarkan perasaan dan kecenderungan alami tubuh Anda sendiri—bukan semata jadwal kuliah atau tuntutan aktivitas yang terpaksa Anda jalani saat ini. Pilih satu jawaban yang paling menggambarkan diri Anda pada setiap nomor. Tidak ada jawaban benar atau salah.',
    'phases' => ['T0'],
    'scoring' => ['method' => 'sum'],
    'items' => [
        // rMEQ-01
        ['Jika Anda sepenuhnya bebas merencanakan hari Anda (tanpa kewajiban kuliah, tugas, atau janji pagi), pukul berapa Anda akan bangun tidur?', 'choice', ['values' => ['5', '4', '3', '2', '1'], 'labels' => ['Pukul 05.00 – 06.30 (pagi)', 'Pukul 06.30 – 07.45 (pagi)', 'Pukul 07.45 – 09.45 (pagi)', 'Pukul 09.45 – 11.00 (menjelang siang)', 'Pukul 11.00 – 12.00 (siang)']]],
        // rMEQ-02
        ['Selama 30 menit pertama setelah Anda bangun tidur pada pagi hari, bagaimana kondisi rasa lelah atau kesegaran Anda?', 'choice', ['values' => ['1', '2', '3', '4'], 'labels' => ['Sangat lelah', 'Cukup lelah', 'Cukup segar', 'Sangat segar']]],
        // rMEQ-03
        ['Pada malam hari, pukul berapa Anda mulai merasa lelah secara alami sehingga merasa membutuhkan tidur?', 'choice', ['values' => ['5', '4', '3', '2', '1'], 'labels' => ['Pukul 20.00 – 21.00 (malam)', 'Pukul 21.00 – 22.15 (malam)', 'Pukul 22.15 – 00.30 (malam s.d. tengah malam)', 'Pukul 00.30 – 01.45 (dini hari)', 'Pukul 01.45 – 03.00 (dini hari)']]],
        // rMEQ-04
        ['Pada rentang waktu manakah sepanjang hari Anda merasa berada pada kondisi terbaik (paling segar, fokus, dan produktif)?', 'choice', ['values' => ['5', '4', '3', '2', '1'], 'labels' => ['Pukul 05.00 – 08.00 (pagi)', 'Pukul 08.00 – 10.00 (pagi)', 'Pukul 10.00 – 17.00 (siang s.d. sore)', 'Pukul 17.00 – 22.00 (sore s.d. malam)', 'Pukul 22.00 – 05.00 (malam s.d. dini hari)']]],
        // rMEQ-05
        ['Sering kali orang menyebut diri mereka sebagai "tipe pagi" (morning person) atau "tipe malam" (evening person). Menurut Anda sendiri, termasuk tipe manakah Anda?', 'choice', ['values' => ['6', '4', '2', '0'], 'labels' => ['Jelas tipe pagi', 'Lebih condong tipe pagi daripada tipe malam', 'Lebih condong tipe malam daripada tipe pagi', 'Jelas tipe malam']]],
    ],
];
