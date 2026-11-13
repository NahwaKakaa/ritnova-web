<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Memulihkan database dari cadangan ritnova:backup (JSONL gzip).
 * Isi setiap tabel yang ada di cadangan DIGANTI seluruhnya. Sebelum memulihkan,
 * keadaan saat ini dicadangkan dulu supaya langkah ini bisa dibatalkan.
 * Jalankan `php artisan migrate --force` lebih dulu bila struktur tabel belum ada.
 */
class RestoreDatabase extends Command
{
    protected $signature = 'ritnova:restore {file? : Nama file di storage/app/private/backups atau path lengkap} {--list : Tampilkan daftar cadangan} {--force : Lewati konfirmasi}';

    protected $description = 'Pulihkan database RITNOVA dari cadangan';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $files = collect($disk->files('backups'))->filter(fn ($f) => str_ends_with($f, '.jsonl.gz'))->sort()->values();

        if ($this->option('list') || ! $this->argument('file')) {
            $this->table(['Cadangan', 'Ukuran'], $files->reverse()->map(fn ($f) => [basename($f), number_format($disk->size($f) / 1024, 1).' KB'])->all());
            $this->line('Pakai: php artisan ritnova:restore <nama-file>');

            return self::SUCCESS;
        }

        $arg = $this->argument('file');
        $path = is_file($arg) ? $arg : $disk->path('backups/'.basename($arg));
        if (! is_file($path)) {
            $this->error("File cadangan tidak ditemukan: {$arg}");

            return self::FAILURE;
        }

        // Baca dulu seluruh isi agar file rusak ketahuan sebelum ada tabel yang dikosongkan.
        $data = [];
        $table = null;
        $gz = gzopen($path, 'rb');
        while (($line = gzgets($gz)) !== false) {
            $row = json_decode($line, true);
            if (! is_array($row)) {
                continue;
            }
            if (count($row) === 1 && isset($row['table']) && is_string($row['table'])) {
                $table = $row['table'];
                $data[$table] = [];
            } elseif ($table !== null) {
                $data[$table][] = $row;
            }
        }
        gzclose($gz);

        if ($data === []) {
            $this->error('Cadangan kosong atau formatnya tidak dikenali.');

            return self::FAILURE;
        }
        $missing = array_values(array_filter(array_keys($data), fn ($t) => ! Schema::hasTable($t)));
        if ($missing) {
            $this->error('Tabel belum ada: '.implode(', ', $missing).'. Jalankan php artisan migrate --force dulu.');

            return self::FAILURE;
        }

        $this->table(['Tabel', 'Baris di cadangan'], collect($data)->map(fn ($rows, $t) => [$t, count($rows)])->values()->all());
        if (! $this->option('force') && ! $this->confirm('Isi tabel di atas akan DIGANTI dengan isi cadangan. Lanjutkan?')) {
            $this->line('Dibatalkan.');

            return self::FAILURE;
        }

        $this->call('ritnova:backup', ['--keep' => 30]);

        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($data) {
                if (DB::getDriverName() === 'sqlite') {
                    DB::statement('PRAGMA defer_foreign_keys = ON'); // berlaku juga di dalam transaksi
                }
                // Kosongkan semua dulu, baru isi; urutan tabel jadi tidak berpengaruh.
                foreach (array_keys($data) as $t) {
                    DB::table($t)->delete();
                }
                foreach ($data as $t => $rows) {
                    foreach (array_chunk($rows, 300) as $chunk) {
                        DB::table($t)->insert($chunk);
                    }
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('Pemulihan selesai: '.count($data).' tabel, '.array_sum(array_map('count', $data)).' baris. Jalankan php artisan optimize:clear.');

        return self::SUCCESS;
    }
}
