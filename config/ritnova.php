<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Zona waktu penelitian
    |--------------------------------------------------------------------------
    | Semua jadwal rilis sesi dan jendela pengukuran dihitung dari jam SERVER
    | pada zona waktu ini, bukan jam perangkat peserta.
    */
    'timezone' => env('APP_TIMEZONE', 'Asia/Makassar'),

    /*
    |--------------------------------------------------------------------------
    | Kebijakan keterlambatan sesi
    |--------------------------------------------------------------------------
    | strict   : sesi kedaluwarsa setelah close_at dan tidak bisa dikejar.
    | catch_up : sesi tetap bisa dikejar berurutan; keterlambatan dicatat
    |            sebagai deviasi paparan.
    */
    'late_policy' => env('RITNOVA_LATE_POLICY', 'catch_up'),

    /*
    |--------------------------------------------------------------------------
    | Aturan penyelesaian sesi
    |--------------------------------------------------------------------------
    | Ambang persentase media harus diuji pada pilot sebelum dipakai final.
    */
    'completion' => [
        'media_threshold' => (float) env('RITNOVA_MEDIA_COMPLETION_THRESHOLD', 0.95),
        'require_quiz' => true,
        'require_action' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Kalkulator Social Jetlag
    |--------------------------------------------------------------------------
    */
    'sjl' => [
        'algorithm_version' => 'sjl-midpoint-v1',
        'min_workdays' => (int) env('RITNOVA_SJL_MIN_WORKDAYS', 2),
        'min_free_days' => (int) env('RITNOVA_SJL_MIN_FREE_DAYS', 1),
        // Durasi tidur di luar rentang ini dianggap "waktu tak masuk akal".
        'min_sleep_minutes' => 60,
        'max_sleep_minutes' => 16 * 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fase pengukuran (protokol revisi 23 Nov 2026)
    |--------------------------------------------------------------------------
    | T0 baseline sebelum program · M2 kuesioner minggu kedua program ·
    | T1 minggu ke-5 (setelah program) · T2 minggu ke-8 (tindak lanjut).
    | Tanggal pasti tetap diatur admin di halaman Pengukuran T0–T2.
    */
    'phases' => ['T0', 'M2', 'T1', 'T2'],

    'phase_labels' => [
        'T0' => 'Sebelum program',
        'M2' => 'Minggu kedua program',
        'T1' => 'Minggu ke-5, setelah program',
        'T2' => 'Minggu ke-8, tindak lanjut',
    ],

    // Fase yang juga memakai catatan tidur harian (sleep diary).
    'diary_phases' => ['T0', 'T1', 'T2'],

    // Target malam catatan tidur per fase diary. Ketentuan malam valid untuk
    // analisis masih menunggu keputusan peneliti; SJL tetap memakai aturan 'sjl'.
    'diary_nights_target' => (int) env('RITNOVA_DIARY_NIGHTS_TARGET', 7),

    // Tema mingguan RITNOVA Learn (Layar 06).
    'weeks' => [
        1 => 'Pahami Ritme',
        2 => 'Kenali Hambatan',
        3 => 'Rancang Aksi',
        4 => 'Pertahankan',
    ],

    'media_labels' => [
        'video' => 'Video',
        'infographic' => 'Infografis',
        'podcast' => 'Podcast',
    ],

    // Infografis tidak punya durasi putar; dianggap selesai setelah digulir sampai akhir
    // dan dibaca minimal sekian detik. Nilai perlu diuji pada pilot.
    'infographic_min_seconds' => 45,

    // Pilihan cepat refleksi mingguan (Layar 13).
    'reflection_barriers' => [
        'tugas_malam' => 'Tugas atau belajar malam',
        'kegiatan_sosial' => 'Kegiatan sosial / organisasi',
        'perangkat' => 'Penggunaan perangkat (HP, game, media sosial)',
        'jadwal_kuliah' => 'Jadwal kuliah tidak menentu',
        'lingkungan' => 'Lingkungan tidur (bising, panas, kamar bersama)',
        'lainnya' => 'Lainnya',
    ],

    // Populasi sasaran dari protokol (ditampilkan di ringkasan kohort). Kosongkan bila belum ditetapkan.
    'target_population' => env('RITNOVA_TARGET_POPULATION') ? (int) env('RITNOVA_TARGET_POPULATION') : null,

    // Kerangka populasi per kelas (Lampiran 02 bagian D: A 114, B 115, C 112; total 341).
    'recruitment_frame' => ['A' => 114, 'B' => 115, 'C' => 112],

    'groups' => [
        'intervention' => 'Intervensi',
        'comparison' => 'Pembanding',
    ],
];
