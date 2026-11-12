<?php

use Illuminate\Support\Facades\Schedule;

/*
| Di hosting cPanel cukup satu cron per menit:
|   * * * * * cd ~/ritnova && php artisan schedule:run >> /dev/null 2>&1
*/

// Notifikasi web saat sesi dirilis (Senin/Rabu/Jumat 19.00 WITA).
Schedule::command('ritnova:notify-releases')->everyMinute()->withoutOverlapping();

// Proses antrean tanpa worker permanen (shared hosting tidak mengizinkan proses abadi).
Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping();

// Detak jantung scheduler: dipakai ritnova:check dan panel peneliti untuk memastikan cron jalan.
Schedule::call(fn () => \Illuminate\Support\Facades\Cache::put('ritnova:scheduler-heartbeat', now()->toDateTimeString(), now()->addDay()))
    ->everyMinute()->name('scheduler-heartbeat');

// Cadangan database harian pukul 02.30 WITA (14 terakhir disimpan di storage/app/backups).
Schedule::command('ritnova:backup')->dailyAt('02:30')->withoutOverlapping();
