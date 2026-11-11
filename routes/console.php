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

// TODO: backup database harian terenkripsi ke penyimpanan eksternal.
