<?php

namespace App\Console\Commands;

use App\Models\Cohort;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Periksa kesiapan server setelah deploy: php artisan ritnova:check */
class CheckDeployment extends Command
{
    protected $signature = 'ritnova:check';

    protected $description = 'Periksa konfigurasi server RITNOVA setelah deploy';

    public function handle(): int
    {
        $rows = [];
        $ok = true;
        $check = function (string $label, bool $pass, string $detail = '') use (&$rows, &$ok) {
            $rows[] = [$pass ? 'OK' : 'PERLU DICEK', $label, $detail];
            $ok = $ok && $pass;
        };

        $check('PHP 8.3 atau lebih baru', version_compare(PHP_VERSION, '8.3.0', '>='), PHP_VERSION);
        $check('APP_KEY terisi', filled(config('app.key')));
        $check('APP_DEBUG mati di production', ! (app()->isProduction() && config('app.debug')), config('app.debug') ? 'true' : 'false');
        $check('APP_URL memakai https', str_starts_with((string) config('app.url'), 'https://'), (string) config('app.url'));
        $check('Zona waktu Asia/Makassar', config('app.timezone') === 'Asia/Makassar', config('app.timezone'));

        try {
            DB::connection()->getPdo();
            $check('Koneksi database', true, config('database.default').' / '.DB::connection()->getDatabaseName());
            $check('Tabel sudah dimigrasi', DB::getSchemaBuilder()->hasTable('cohort_session_schedules'));
            $admin = User::where('role', User::ROLE_ADMIN)->first();
            $check('Akun admin ada', (bool) $admin, $admin?->username ?? '');
            $check('Kata sandi admin sudah diganti', $admin && ! \Hash::check('ganti-password-ini', $admin->password));
            $check('Kohort & jadwal 12 sesi', Cohort::first()?->schedules()->count() === 12);
        } catch (\Throwable $e) {
            $check('Koneksi database', false, $e->getMessage());
        }

        $check('Ekstensi zip (ekspor XLSX)', class_exists(\ZipArchive::class), class_exists(\ZipArchive::class) ? 'aktif' : 'aktifkan zip di Select PHP Version');
        $check('Folder storage bisa ditulis', is_writable(storage_path('framework')) && is_writable(storage_path('logs')));
        $check('Aset tampilan sudah dibangun', is_file(public_path('build/manifest.json')), 'public/build');
        $beat = Cache::get('ritnova:scheduler-heartbeat');
        $check('Cron scheduler berjalan', $beat && now()->diffInMinutes($beat, true) < 5, $beat ? 'terakhir '.$beat : 'belum pernah');

        $this->table(['Status', 'Pemeriksaan', 'Keterangan'], $rows);
        $ok ? $this->info('Server siap.') : $this->warn('Ada yang perlu dibereskan (lihat baris PERLU DICEK).');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
