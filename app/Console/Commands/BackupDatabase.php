<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Cadangan harian seluruh tabel (JSON per baris, dikompres gzip) ke storage/app/backups.
 * Tidak butuh mysqldump, jadi jalan di shared hosting. Menyimpan 14 cadangan terakhir.
 * Unduh berkala dari File Manager dan simpan di luar hosting.
 */
class BackupDatabase extends Command
{
    protected $signature = 'ritnova:backup {--keep=14}';

    protected $description = 'Buat cadangan database RITNOVA';

    private const SKIP = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $name = 'backups/ritnova-'.now()->format('Ymd-His-v').'.jsonl.gz';
        $path = $disk->path($name);
        @mkdir(dirname($path), 0750, true);
        $gz = gzopen($path, 'wb6');

        $tables = collect(DB::getSchemaBuilder()->getTableListing(schemaQualified: false))
            ->reject(fn ($t) => in_array($t, self::SKIP, true))->values();
        $count = 0;
        foreach ($tables as $table) {
            gzwrite($gz, json_encode(['table' => $table])."\n");
            DB::table($table)->orderBy(DB::raw('1'))->lazy(500)->each(function ($row) use ($gz, &$count) {
                gzwrite($gz, json_encode($row, JSON_UNESCAPED_UNICODE)."\n");
                $count++;
            });
        }
        gzclose($gz);

        $old = collect($disk->files('backups'))->sort()->values();
        $old->slice(0, max(0, $old->count() - (int) $this->option('keep')))->each(fn ($f) => $disk->delete($f));

        $this->info("Cadangan {$name}: {$tables->count()} tabel, {$count} baris.");

        return self::SUCCESS;
    }
}
