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

    'phases' => ['T0', 'T1', 'T2'],

    'phase_labels' => [
        'T0' => 'Sebelum program',
        'T1' => 'Akhir minggu pertama',
        'T2' => 'Akhir minggu keempat',
    ],

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

    'groups' => [
        'intervention' => 'Intervensi',
        'comparison' => 'Pembanding',
    ],
];
