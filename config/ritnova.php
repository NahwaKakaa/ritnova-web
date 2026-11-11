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

    'groups' => [
        'intervention' => 'Intervensi',
        'comparison' => 'Pembanding',
    ],
];
